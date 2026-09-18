<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>{{ $title }}</title>
  <style>
    body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 10px; color: #333; }
    h1 { font-size: 16px; color: #1B4F72; margin-bottom: 4px; }
    .meta { font-size: 9px; color: #888; margin-bottom: 12px; }
    table { width: 100%; border-collapse: collapse; }
    th {
      background: #1B4F72;
      color: #fff;
      font-size: 9px;
      font-weight: bold;
      text-transform: uppercase;
      padding: 6px 4px;
      text-align: left;
    }
    td { padding: 5px 4px; border-bottom: 1px solid #e0e0e0; font-size: 10px; }
    tr:nth-child(even) td { background: #f8f9fa; }
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
</body>
</html>
