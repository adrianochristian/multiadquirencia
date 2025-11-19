<?php

namespace App\Console\Commands;

use App\Models\Subacquirer;
use Illuminate\Console\Command;

class SubacquirerModeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subacquirer:mode {mode? : mock ou real}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Alterna entre modo mock (local) e real (APIs externas) para subadquirentes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $mode = $this->argument('mode');

        if (!$mode) {
            $this->showCurrentStatus();
            return 0;
        }

        if (!in_array($mode, ['mock', 'real'])) {
            $this->error("Modo inválido. Use 'mock' ou 'real'");
            return 1;
        }

        $this->updateEnvFile('SUBACQUIRER_MODE', $mode);
        $this->updateDatabase($mode);

        $this->info("✅ Modo alterado para: {$mode}");
        $this->newLine();
        $this->showCurrentStatus();

        return 0;
    }

    /**
     * Mostrar status atual
     */
    protected function showCurrentStatus()
    {
        $currentMode = env('SUBACQUIRER_MODE', 'mock');

        $this->info("📊 Status Atual das Subadquirentes");
        $this->line("─────────────────────────────────────");
        $this->line("Modo: <fg=cyan>{$currentMode}</>");
        $this->newLine();

        $subacquirers = Subacquirer::all();

        if ($subacquirers->isEmpty()) {
            $this->warn("⚠️  Nenhuma subadquirente cadastrada. Execute: php artisan db:seed");
            return;
        }

        $this->table(
            ['Nome', 'Código', 'Base URL', 'Status'],
            $subacquirers->map(function ($sub) {
                return [
                    $sub->name,
                    $sub->code,
                    $sub->base_url === 'mock' ? '🏠 Mock Local' : "🌐 {$sub->base_url}",
                    $sub->is_active ? '✅ Ativo' : '❌ Inativo',
                ];
            })
        );

        $this->newLine();
        $this->line("💡 Dicas:");
        $this->line("  • php artisan subacquirer:mode mock  - Usar mock local");
        $this->line("  • php artisan subacquirer:mode real  - Usar APIs externas");
        $this->line("  • php artisan subacquirer:mode       - Ver status atual");
    }

    /**
     * Atualizar arquivo .env
     */
    protected function updateEnvFile($key, $value)
    {
        $envFile = base_path('.env');

        if (!file_exists($envFile)) {
            $this->warn("Arquivo .env não encontrado");
            return;
        }

        $content = file_get_contents($envFile);

        if (preg_match("/^{$key}=.*/m", $content)) {
            $content = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $content);
        } else {
            $content .= "\n{$key}={$value}\n";
        }

        file_put_contents($envFile, $content);
        $this->call('config:clear');
    }

    /**
     * Atualizar base_url das subadquirentes no banco
     */
    protected function updateDatabase($mode)
    {
        $subacquirers = [
            'subadq_a' => env('SUBACQUIRER_A_BASE_URL', 'https://0acdeaee-1729-4d55-80eb-d54a125e5e18.mock.pstmn.io'),
            'subadq_b' => env('SUBACQUIRER_B_BASE_URL', 'https://ef8513c8-fd99-4081-8963-573cd135e133.mock.pstmn.io'),
        ];

        foreach ($subacquirers as $code => $realUrl) {
            $subacquirer = Subacquirer::where('code', $code)->first();

            if (!$subacquirer) {
                continue;
            }

            $newUrl = $mode === 'mock' ? 'mock' : $realUrl;
            $subacquirer->base_url = $newUrl;
            $subacquirer->save();
        }
    }
}
