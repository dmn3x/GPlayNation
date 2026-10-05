<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Simulated payment · {{ $trx->order_id }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-bg font-sans text-fg antialiased">
    <main class="mx-auto flex min-h-screen max-w-lg items-center px-6 py-12">
        <div class="w-full space-y-6">
            <div class="flex items-center justify-center">
                <span class="rounded-full bg-elevated px-4 py-1.5 text-center text-sm font-semibold uppercase tracking-wide text-amber-400">
                    SIMULATED PAYMENT — GPlayNation Dev Gateway
                </span>
            </div>

            <div class="card space-y-6 p-6">
                <div class="text-center">
                    <p class="text-sm text-faint">Order {{ $trx->order_id }}</p>
                    <p class="text-[27px] font-semibold leading-[37.8px]">{{ \App\Support\Money::format($trx->total) }}</p>
                    <p class="text-[16.2px] text-muted">{{ $trx->iap?->name ?? $trx->game?->name }}</p>
                </div>

                <fieldset class="space-y-3">
                    <legend class="section-heading">Payment method</legend>
                    @php
                        $methods = [
                            ['value' => 'e_wallet', 'label' => 'E-Wallet', 'icon' => 'wallet'],
                            ['value' => 'virtual_account', 'label' => 'Virtual Account', 'icon' => 'banknote'],
                            ['value' => 'credit_card', 'label' => 'Credit Card', 'icon' => 'document'],
                        ];
                    @endphp
                    @foreach ($methods as $i => $method)
                        <label class="flex cursor-pointer items-center gap-4 rounded-xl border border-border-strong bg-elevated p-4 transition hover:border-accent has-[:checked]:border-accent">
                            <input type="radio" name="method_choice" value="{{ $method['value'] }}" @checked($i === 0) class="h-4 w-4 accent-accent">
                            <x-icon :name="$method['icon']" class="h-5 w-5 text-subtle" />
                            <span class="font-medium">{{ $method['label'] }}</span>
                        </label>
                    @endforeach
                </fieldset>

                <div class="rounded-xl border border-border bg-elevated p-4 text-sm">
                    <p class="text-faint">Simulated instrument <span class="font-semibold text-subtle">(fake — for display only)</span></p>
                    <p id="gateway-instrument" class="mt-1 font-mono font-semibold">GPN-PAY-0812-XXXX</p>
                </div>

                <div class="space-y-3">
                    <form method="POST" action="{{ route('gateway.complete', $trx->order_id) }}" data-gateway-form>
                        @csrf
                        <input type="hidden" name="method" value="e_wallet">
                        <input type="hidden" name="outcome" value="settlement">
                        <button type="submit" class="btn-primary w-full !bg-success hover:!bg-success/90">
                            <x-icon name="check" class="h-5 w-5" /> Pay (success)
                        </button>
                    </form>

                    <form method="POST" action="{{ route('gateway.complete', $trx->order_id) }}" data-gateway-form>
                        @csrf
                        <input type="hidden" name="method" value="e_wallet">
                        <input type="hidden" name="outcome" value="deny">
                        <button type="submit" class="btn-ghost w-full !border-danger !text-danger hover:!border-danger">
                            <x-icon name="x" class="h-5 w-5" /> Fail
                        </button>
                    </form>

                    <form method="POST" action="{{ route('gateway.complete', $trx->order_id) }}" data-gateway-form>
                        @csrf
                        <input type="hidden" name="method" value="e_wallet">
                        <input type="hidden" name="outcome" value="expire">
                        <button type="submit" class="btn-ghost w-full">
                            <x-icon name="alert" class="h-5 w-5" /> Expire
                        </button>
                    </form>
                </div>

                <p class="text-center text-sm text-faint">No real money moves. This drives the app's signed webhook pipeline.</p>
            </div>
        </div>
    </main>

    <script>
        var instruments = {
            e_wallet: 'GPN-PAY-0812-XXXX',
            virtual_account: '8808 1234 5678 9012',
            credit_card: '4111 11•• •••• 1111'
        };
        function syncInstrument() {
            var chosen = document.querySelector('input[name="method_choice"]:checked');
            if (! chosen) {
                return;
            }
            document.getElementById('gateway-instrument').textContent = instruments[chosen.value] || '';
        }
        document.querySelectorAll('input[name="method_choice"]').forEach(function (radio) {
            radio.addEventListener('change', syncInstrument);
        });
        syncInstrument();
        document.querySelectorAll('form[data-gateway-form]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                var chosen = document.querySelector('input[name="method_choice"]:checked');
                if (! chosen) {
                    event.preventDefault();
                    return;
                }
                form.querySelector('input[name="method"]').value = chosen.value;
            });
        });
    </script>
</body>
</html>
