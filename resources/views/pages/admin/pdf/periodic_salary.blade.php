<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        @page {
            margin: 15mm;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            font-size: 10pt;
            text-align: center;
        }

        h4 {
            font-size: 12pt;
            line-height: 1.25;
            margin: 0 0 5mm 0;
            padding: 0;
            text-transform: uppercase;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 0.3px solid black;
            padding: 4.5px;
            text-align: left;
            vertical-align: top;
        }

        thead {
            display: table-header-group;
        }

        tr {
            page-break-inside: avoid;
        }

        small {
            font-size: 8pt;
        }

        .text-center {
            text-align: center;
        }
    </style>
</head>

<body>
    <h4>
        {{ $title }}<br>
        PERIODE {{ $periode }}<br>
        {{ $school }}
    </h4>

    <table>
        <thead>
            <tr>
                <th class="text-center" style="width: 8%;">NO</th>
                <th>
                    Pegawai<br>
                    <small>Nama | Nomor Induk Pegawai</small>
                </th>
                <th>
                    Berkala<br>
                    <small>TMT | Masa Kerja</small>
                </th>
                <th>
                    Berkala Berikutnya<br>
                    <small>TMT | Tahun Ke</small>
                </th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
            <tr>
                <td class="text-center">{{ $loop->iteration }}</td>
                <td>
                    <b>{{ $row['name'] }}</b><br>
                    <small>{{ $row['nip'] }}</small>
                </td>
                <td>
                    {{ $row['tmt'] }}<br>
                    <small>MK. <b>{{ $row['masa_kerja'] }}</b></small>
                </td>
                <td>
                    {{ $row['tmt_berikut'] }}<br>
                    <small>Tahun ke. <b>{{ $row['tahun_ke'] }}</b></small>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="4" class="text-center">Tidak ada data.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</body>

</html>