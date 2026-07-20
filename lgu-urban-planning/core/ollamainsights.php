<?php

/**
 * OllamaInsights
 * --------------
 * Same purpose as AIInsights.php (turns aggregated report data into a
 * short narrative), but calls a locally self-hosted Ollama model instead
 * of the Anthropic API. No API key, no per-call cost — just the compute
 * on your own server.
 *
 * Setup on your server:
 *   curl -fsSL https://ollama.com/install.sh | sh
 *   ollama pull llama3.2
 *
 * Usage (identical to AIInsights):
 *   require_once __DIR__ . '/../core/OllamaInsights.php';
 *   $aiInsights = new OllamaInsights();
 *   $aiNarrative = $aiInsights->generate($chartData, $inspectorWorkload, $selectedYear ?? date('Y'));
 *
 * Swap between this and AIInsights.php by changing one line in index.php —
 * both classes expose the same generate() method.
 */
class OllamaInsights
{
    private string $ollamaUrl;
    private string $model;
    private string $cacheDir;
    private int $cacheTtlSeconds = 3600;
    private int $timeoutSeconds;

    public function __construct(
        string $ollamaUrl = 'http://localhost:11434/api/chat',
        string $model = 'llama3.2',
        ?string $cacheDir = null,
        int $timeoutSeconds = 60 // local models on CPU can be slow — give it room
    ) {
        $this->ollamaUrl = $ollamaUrl;
        $this->model = $model;
        $this->cacheDir = $cacheDir ?? (__DIR__ . '/../cache/ai_insights');
        $this->timeoutSeconds = $timeoutSeconds;

        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0775, true);
        }
    }

    public function generate(array $chartData, array $inspectorWorkload, int $year): string
    {
        $cacheKey = md5('ollama_' . $this->model . json_encode([$chartData, $inspectorWorkload, $year]));
        $cacheFile = $this->cacheDir . '/' . $cacheKey . '.json';

        $cached = $this->readCache($cacheFile);
        if ($cached !== null) {
            return $cached;
        }

        $summary = $this->summarizeForPrompt($chartData, $inspectorWorkload, $year);
        $narrative = $this->callOllama($summary);

        if ($narrative !== '') {
            $this->writeCache($cacheFile, $narrative);
        }

        return $narrative;
    }

    public function clearCache(): void
    {
        foreach (glob($this->cacheDir . '/*.json') ?: [] as $file) {
            @unlink($file);
        }
    }

    // ----------------------------------------------------------------
    // Internal
    // ----------------------------------------------------------------

    private function summarizeForPrompt(array $chartData, array $inspectorWorkload, int $year): string
    {
        $facts = [];
        $facts[] = "Report year: {$year}";

        // --- Status counts (PHP-verified, model must not alter these) ---
        $status = $chartData['status'] ?? ['Approved' => 0, 'Rejected' => 0, 'Pending' => 0];
        $totalApps = array_sum($status);
        $facts[] = "FACT: Total applications in {$year} = {$totalApps}";
        $facts[] = "FACT: Approved = {$status['Approved']}";
        $facts[] = "FACT: Rejected = {$status['Rejected']}";
        $facts[] = "FACT: Pending/Other (includes submitted, under review, etc.) = {$status['Pending']}";

        // --- Year-over-year, computed here (not left for the model to subtract) ---
        $yoy = $chartData['yoy_comparison'] ?? ['current' => 0, 'previous' => 0];
        $prevYear = $year - 1;
        $delta = $yoy['current'] - $yoy['previous'];
        if ($yoy['previous'] > 0) {
            $pctChange = round(($delta / $yoy['previous']) * 100, 1);
            $direction = $delta > 0 ? 'an increase of' : ($delta < 0 ? 'a decrease of' : 'no change —');
            $facts[] = "FACT: Compared to {$prevYear} ({$yoy['previous']} applications), {$year} ({$yoy['current']} applications) shows {$direction} " . abs($pctChange) . "%.";
        } elseif ($yoy['current'] > 0) {
            $facts[] = "FACT: There is no {$prevYear} data to compare against (0 applications that year), so no year-over-year percentage can be calculated. Do not invent one.";
        } else {
            $facts[] = "FACT: No year-over-year comparison is available.";
        }

        // --- Busiest month, computed here (not a raw dump for the model to sum) ---
        $months = $chartData['months'] ?? [];
        $monthsWithData = array_filter($months, fn($c) => $c > 0);
        if (!empty($monthsWithData)) {
            arsort($monthsWithData);
            $topMonth = array_key_first($monthsWithData);
            $topCount = $monthsWithData[$topMonth];
            $activeMonths = count($monthsWithData);
            $facts[] = "FACT: The busiest month was {$topMonth} with {$topCount} application(s). Applications occurred in {$activeMonths} month(s) total during {$year}.";
        } else {
            $facts[] = "FACT: No monthly application data recorded for {$year}.";
        }

        // --- Barangays (already just a ranked list — safe as-is) ---
        $barangays = $chartData['barangays'] ?? [];
        if (!empty($barangays)) {
            $brgyLine = [];
            foreach ($barangays as $name => $count) {
                $brgyLine[] = "{$name}={$count}";
            }
            $facts[] = "FACT: Top barangays by TOTAL application count, all statuses mixed together (NOT approved-only): " . implode(', ', $brgyLine);
        }

        // --- Inspector workload ---
        if (!empty($inspectorWorkload)) {
            $counts = array_column($inspectorWorkload, 'total_inspections');
            $avg = count($counts) ? round(array_sum($counts) / count($counts), 1) : 0;
            $iwLine = [];
            foreach ($inspectorWorkload as $row) {
                $name = $row['inspector_name'] ?? 'Unknown';
                $total = $row['total_inspections'] ?? 0;
                $iwLine[] = "{$name}={$total}";
            }
            $facts[] = "FACT: Inspector workload (total inspections, all-time): " . implode(', ', $iwLine);
            $facts[] = "FACT: Average inspections per inspector = {$avg}";
        }

        return implode("\n", $facts);
    }

    private function callOllama(string $dataSummary): string
    {
        // Small local models follow short, explicit instructions much more
        // reliably than long nuanced ones — keep the prompt tight.
        $prompt = <<<PROMPT
Summarize this local government permit dashboard data for an admin.

Below is a list of FACT lines. Every number you need has already been
calculated for you.

STRICT RULES:
1. Do NOT perform any addition, subtraction, percentages, or other math.
   Every fact you need is already computed in the FACT lines — just restate
   them in plain language.
2. Do NOT combine two FACT lines into a new number (e.g. do not add two
   months together, do not merge barangay counts with status counts).
3. Do NOT mention a status (Approved/Rejected/Pending) for barangay data —
   barangay counts mix all statuses together, this is explicitly stated.
4. Only use numbers that appear in a FACT line below. If something is not
   in a FACT line, do not mention it.
5. If a FACT line says a comparison is unavailable, say it's unavailable —
   do not invent a percentage anyway.
6. MANDATORY: if the "Pending/Other" FACT is greater than 0, you MUST
   include a bullet point stating that number — these are applications
   still awaiting action, and the admin needs to see this every time. Do
   not omit it even if the total is small (e.g. 1).

Write exactly:
- One overall summary sentence, using only FACT numbers.
- 3 to 6 bullet points, each starting with "- ", each restating one FACT
  line in plain language. (Rule 6 above is mandatory when it applies —
  count it as one of your bullets, don't skip it to stay under a limit.)
- Keep it under 150 words. No headers, no markdown besides the bullets.

FACTS:
{$dataSummary}
PROMPT;

        $payload = json_encode([
            'model' => $this->model,
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
            'stream' => false,
            'options' => [
                'temperature' => 0.2, // lower = stricter adherence to the data, less creative drift/hallucination
            ],
        ]);

        $ch = curl_init($this->ollamaUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => $this->timeoutSeconds,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError || $httpCode !== 200 || !$response) {
            error_log("OllamaInsights: call failed (HTTP {$httpCode}): {$curlError}. Is 'ollama serve' running?");
            return '';
        }

        $data = json_decode($response, true);
        $text = $data['message']['content'] ?? '';

        return trim($text);
    }

    private function readCache(string $file): ?string
    {
        if (!is_file($file)) {
            return null;
        }
        $raw = @file_get_contents($file);
        if ($raw === false) {
            return null;
        }
        $decoded = json_decode($raw, true);
        if (!$decoded || !isset($decoded['generated_at'], $decoded['text'])) {
            return null;
        }
        if (time() - $decoded['generated_at'] > $this->cacheTtlSeconds) {
            return null;
        }
        return $decoded['text'];
    }

    private function writeCache(string $file, string $text): void
    {
        @file_put_contents($file, json_encode([
            'generated_at' => time(),
            'text' => $text,
        ]));
    }
}