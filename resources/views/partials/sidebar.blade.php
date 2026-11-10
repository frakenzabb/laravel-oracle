<style>
    .app-layout {
        display: grid;
        grid-template-columns: 252px minmax(0, 1fr);
        min-height: 100vh;
        max-width: 1920px;
        margin: 0 auto;
    }
    .app-sidebar {
        position: sticky;
        top: 0;
        height: 100vh;
        padding: 1.5rem 1rem;
        border-right: 1px solid #e7ecf3;
        background: #fff;
    }
    .sidebar-brand {
        display: flex;
        align-items: center;
        gap: .75rem;
        padding: 0 .65rem 1.5rem;
        color: #172b4d;
        font-size: 1.05rem;
        font-weight: 750;
        text-decoration: none;
    }
    .sidebar-brand-icon {
        display: grid;
        width: 40px;
        height: 40px;
        place-items: center;
        border-radius: 12px;
        color: #1769e8;
        background: #eaf2ff;
    }
    .sidebar-label {
        padding: .5rem .75rem;
        color: #718096;
        font-size: .7rem;
        font-weight: 750;
        letter-spacing: .08em;
        text-transform: uppercase;
    }
    .sidebar-nav { display: grid; gap: .35rem; }
    .sidebar-link {
        display: flex;
        align-items: center;
        gap: .75rem;
        width: 100%;
        padding: .75rem;
        border: 0;
        border-radius: 10px;
        background: transparent;
        color: #43536b;
        font: inherit;
        text-align: left;
        text-decoration: none;
        transition: color .16s ease, background-color .16s ease;
    }
    .sidebar-link:hover, .sidebar-link.active {
        background: #edf4ff;
        color: #1769e8;
    }
    .sidebar-link:focus-visible {
        outline: 3px solid rgba(23, 105, 232, .28);
        outline-offset: 2px;
    }
    .sidebar-link-icon {
        display: grid;
        width: 25px;
        place-items: center;
        font-size: 1.1rem;
        font-weight: 700;
    }
    .app-content { min-width: 0; }
    @media (max-width: 767.98px) {
        .app-layout { grid-template-columns: minmax(0, 1fr); align-content: start; }
        .app-sidebar {
            position: static;
            z-index: 5;
            height: auto;
            padding: .75rem;
            border-right: 0;
            border-bottom: 1px solid #e7ecf3;
        }
        .sidebar-brand { padding: .25rem .4rem .65rem; }
        .sidebar-label { padding: .35rem .5rem; }
        .sidebar-nav { grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .25rem; }
        .sidebar-link { justify-content: center; gap: .4rem; padding: .6rem .35rem; font-size: .82rem; }
        .sidebar-link-icon { width: auto; }
    }
</style>

<aside class="app-sidebar" aria-label="Navigasi utama">
    <a class="sidebar-brand" href="{{ route('barang.index') }}">
        <span class="sidebar-brand-icon" aria-hidden="true">▣</span>
        <span>Manajemen Gudang</span>
    </a>
    <div class="sidebar-label">Transaksi</div>
    <nav class="sidebar-nav">
        <a class="sidebar-link {{ $activePage === 'receipt' ? 'active' : '' }}" href="{{ route('barang.index') }}" @if($activePage === 'receipt') aria-current="page" @endif>
            <span class="sidebar-link-icon" aria-hidden="true">⌂</span>
            <span>Dashboard</span>
        </a>
        <a class="sidebar-link {{ $activePage === 'input' ? 'active' : '' }}" href="{{ route('penerimaan-barang.input') }}" @if($activePage === 'input') aria-current="page" @endif>
            <span class="sidebar-link-icon" aria-hidden="true">＋</span>
            <span>Barang Masuk (Input)</span>
        </a>
        <a class="sidebar-link {{ $activePage === 'outgoing' ? 'active' : '' }}" href="{{ route('bon-keluar.index') }}" @if($activePage === 'outgoing') aria-current="page" @endif>
            <span class="sidebar-link-icon" aria-hidden="true">↗</span>
            <span>Barang Keluar</span>
        </a>
    </nav>
    <div class="sidebar-label mt-3">Persediaan</div>
    <nav class="sidebar-nav">
        <a
            class="sidebar-link"
            href="{{ $activePage === 'receipt' ? '#availableStockTable' : route('barang.index').'#availableStockTable' }}"
            @if($activePage === 'receipt') aria-label="Lihat daftar barang tersedia" @endif
        >
            <span class="sidebar-link-icon" aria-hidden="true">▤</span>
            <span>Barang Tersedia</span>
        </a>
    </nav>
</aside>
