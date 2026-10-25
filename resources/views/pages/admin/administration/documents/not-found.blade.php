<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Berkas Tidak Ditemukan</title>
    <style>
        :root {
            --bg: #f8fafc;
            --card: #ffffff;
            --border: #e2e8f0;
            --text: #0f172a;
            --muted: #64748b;
            --accent: #f59e0b;
            --accent-bg: #fef3c7;
            --btn: #0f172a;
            --btn-text: #ffffff;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --bg: #0f172a;
                --card: #1e293b;
                --border: #334155;
                --text: #f1f5f9;
                --muted: #94a3b8;
                --accent: #fbbf24;
                --accent-bg: #422006;
                --btn: #f1f5f9;
                --btn-text: #0f172a;
            }
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            background: var(--bg);
            color: var(--text);
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
        }

        .card {
            width: 100%;
            max-width: 28rem;
            padding: 2rem 1.75rem;
            text-align: center;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 1rem;
        }

        .icon {
            width: 4rem;
            height: 4rem;
            margin: 0 auto 1.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: var(--accent-bg);
            color: var(--accent);
        }

        h1 {
            margin: 0 0 .5rem;
            font-size: 1.25rem;
            font-weight: 700;
        }

        p {
            margin: 0;
            font-size: .9rem;
            line-height: 1.55;
            color: var(--muted);
        }

        .meta {
            margin: 1.25rem 0 0;
            padding: .75rem 1rem;
            text-align: left;
            font-size: .8rem;
            color: var(--muted);
            border: 1px dashed var(--border);
            border-radius: .75rem;
            word-break: break-word;
        }

        .meta strong {
            color: var(--text);
            font-weight: 600;
        }

        .meta div+div {
            margin-top: .25rem;
        }

        .actions {
            margin-top: 1.5rem;
            display: flex;
            gap: .5rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        button {
            cursor: pointer;
            padding: .6rem 1.1rem;
            font-size: .85rem;
            font-weight: 600;
            border-radius: .65rem;
            border: 1px solid var(--border);
            background: transparent;
            color: var(--text);
        }

        button.primary {
            background: var(--btn);
            color: var(--btn-text);
            border-color: var(--btn);
        }
    </style>
</head>

<body>
    <main class="card">
        <div class="icon" aria-hidden="true">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                <path d="M14 2v6h6" />
                <path d="m9.5 12.5 5 5" />
                <path d="m14.5 12.5-5 5" />
            </svg>
        </div>

        <h1>Berkas Tidak Ditemukan</h1>
        <p>
            Data dokumen ini tercatat di sistem, tetapi berkas fisiknya tidak tersedia di penyimpanan.
            Silakan unggah ulang berkasnya, atau hubungi administrator jika masalah ini berlanjut.
        </p>

        @if (!empty($item))
        <div class="meta">
            @if (filled($item->document_name))
            <div><strong>Dokumen:</strong> {{ $item->document_name }}</div>
            @endif
            @if (filled($item->original_filename))
            <div><strong>Nama berkas:</strong> {{ $item->original_filename }}</div>
            @endif
            @if (filled(optional($item->staff)->name))
            <div><strong>Pegawai:</strong> {{ $item->staff->name }}</div>
            @endif
        </div>
        @endif

        <div class="actions">
            <button type="button" onclick="history.length > 1 ? history.back() : window.close()">Kembali</button>
            <button type="button" class="primary" onclick="window.close()">Tutup Tab</button>
        </div>
    </main>
</body>

</html>