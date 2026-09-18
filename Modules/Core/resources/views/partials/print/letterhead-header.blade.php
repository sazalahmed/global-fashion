{{-- Letterhead header: logo (or business name) with the document title to
     its right. Expects $brand, $docTitle. --}}
<table class="head-table">
  <tr>
    <td>
      @if($brand['logoUrl'])
        <img src="{{ $brand['logoUrl'] }}" alt="{{ $brand['name'] }}" class="logo-img" width="230">
      @else
        <div class="logo-name">{{ $brand['name'] }}</div>
      @endif
    </td>
    <td class="title-cell">
      <div class="doc-title"><span>{{ $docTitle }}</span></div>
    </td>
  </tr>
</table>
