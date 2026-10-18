<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DiningTable;
use App\Support\IdMessages;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DiningTableController extends Controller
{
    public function index(): View
    {
        return view('admin.tables.index', [
            'tables' => DiningTable::orderBy('number')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'number' => ['required', 'integer', 'min:1', 'max:999', Rule::unique('tables', 'number')],
        ], IdMessages::rules(), ['number' => 'Nomor meja']);

        DiningTable::create(['number' => $data['number']]);

        return redirect()
            ->route('admin.tables.index')
            ->with('success', "Meja {$data['number']} ditambahkan.");
    }

    public function toggle(DiningTable $table): RedirectResponse
    {
        $table->update(['is_active' => ! $table->is_active]);

        return back()->with(
            'success',
            $table->is_active ? "Meja {$table->number} diaktifkan." : "Meja {$table->number} dinonaktifkan."
        );
    }

    public function regenerate(DiningTable $table): RedirectResponse
    {
        $table->regenerateToken();

        return back()->with('success', "QR Meja {$table->number} diganti. QR lama tidak berlaku lagi, cetak ulang QR yang baru.");
    }

    public function destroy(DiningTable $table): RedirectResponse
    {
        // Riwayat pesanan aman: orders menyimpan nomor meja sebagai salinan, bukan relasi.
        $table->delete();

        return redirect()
            ->route('admin.tables.index')
            ->with('success', "Meja {$table->number} dihapus.");
    }

    /**
     * Halaman cetak QR. Satu meja (?table=ID) atau semua meja aktif.
     */
    public function print(Request $request): View
    {
        $tables = DiningTable::query()
            ->where('is_active', true)
            ->when($request->integer('table') > 0, fn ($q) => $q->whereKey($request->integer('table')))
            ->orderBy('number')
            ->get();

        $qrs = $tables->mapWithKeys(fn (DiningTable $table) => [$table->id => $this->qrSvg($table->scanUrl())]);

        return view('admin.tables.print', compact('tables', 'qrs'));
    }

    /** QR berbentuk SVG (tajam di ukuran berapa pun, tanpa ekstensi imagick). */
    private function qrSvg(string $url): string
    {
        $renderer = new ImageRenderer(new RendererStyle(400, 1), new SvgImageBackEnd());
        $svg = (new Writer($renderer))->writeString($url);

        // Buang deklarasi XML di awal supaya aman disisipkan langsung ke HTML.
        return preg_replace('/^<\?xml.*?\?>\s*/s', '', $svg);
    }
}
