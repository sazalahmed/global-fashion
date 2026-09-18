<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>{{ $title }}</title>
  <style>
    body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 12px; color: #333; margin: 24px; }
    h1 { font-size: 18px; color: #1B4F72; margin-bottom: 4px; }
    .meta { font-size: 11px; color: #888; margin-bottom: 16px; }
    table { width: 100%; border-collapse: collapse; }
    th {
      background: #1B4F72;
      color: #fff;
      font-size: 11px;
      font-weight: bold;
      text-transform: uppercase;
      padding: 8px 6px;
      text-align: left;
    }
    td { padding: 7px 6px; border-bottom: 1px solid #e0e0e0; font-size: 12px; }
    tr:nth-child(even) td { background: #f8f9fa; }
    @media print {
      body { margin: 0; }
      @page { size: landscape; margin: 12mm; }
    }
  </style>
</head>
<body>
  <h1>{{ $title }}</h1>
  <div class="meta">Generated on {{ now()->format('d M Y, h:i A') }} | {{ count($rows) }} records</div>

  <table>
    <thead>
      <tr>
        @foreach($headings as $heading)
          <th>{{ $heading }}</th>
        @endforeach
      </tr>
    </thead>
    <tbody>
      @foreach($rows as $row)
        <tr>
          @foreach($row as $cell)
            <td>{{ $cell }}</td>
          @endforeach
        </tr>
      @endforeach
    </tbody>
  </table>

  <script>
    'use strict';
    window.addEventListener('load', function () { window.print(); });
  </script>
</body>
</html>
