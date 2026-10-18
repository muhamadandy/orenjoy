<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cetak QR Meja · Orenjoy</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 24px; font-family: system-ui, -apple-system, "Segoe UI", sans-serif; color: #3b2a1e; background: #fff; }
        .toolbar { max-width: 960px; margin: 0 auto 20px; display: flex; gap: 12px; align-items: center; justify-content: space-between; }
        .toolbar button { padding: 10px 18px; border: 0; border-radius: 8px; background: #cc4a08; color: #fff; font-size: 15px; cursor: pointer; }
        .grid { max-width: 960px; margin: 0 auto; display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
        .card { border: 2px dashed #cc4a08; border-radius: 16px; padding: 24px; text-align: center; break-inside: avoid; page-break-inside: avoid; }
        .brand { font-size: 28px; font-weight: 800; color: #cc4a08; }
        .brand span { color: #ffb347; }
        .qr { width: 260px; height: 260px; margin: 12px auto; }
        .qr svg { width: 100%; height: 100%; display: block; }
        .hint { margin: 0; font-size: 15px; }
        .number { margin: 8px 0 0; font-size: 34px; font-weight: 800; }
        .empty { text-align: center; color: #7a6a5c; }
        @media print {
            body { padding: 0; }
            .toolbar { display: none; }
            .grid { gap: 12px; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <span>{{ $tables->count() }} QR siap dicetak</span>
        <button type="button" onclick="window.print()">Cetak</button>
    </div>

    @if ($tables->isEmpty())
        <p class="empty">Tidak ada meja aktif untuk dicetak.</p>
    @else
        <div class="grid">
            @foreach ($tables as $table)
                <div class="card">
                    <div class="brand">Oren<span>joy</span></div>
                    <div class="qr" role="img" aria-label="QR code Meja {{ $table->number }}">{!! $qrs[$table->id] !!}</div>
                    <p class="hint">Scan untuk melihat menu dan memesan</p>
                    <p class="number">Meja {{ $table->number }}</p>
                </div>
            @endforeach
        </div>
    @endif
</body>
</html>
