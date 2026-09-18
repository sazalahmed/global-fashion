<link rel="icon" type="image/png" href="{{ $companyFavicon ?? asset('website/assets/images/favicon.png') }}">
<style>
    * {
        box-sizing: border-box;
    }

    body {
        margin: 0;
        background: #dcdcdc;
        font-family: Arial, Helvetica, sans-serif;
        color: #000;
        font-size: 14px;
        padding: 32px 0;
    }

    .inv-actions {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        background: #1B4F72;
        color: #fff;
        padding: 10px 24px;
        text-align: center;
        z-index: 100;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    }

    .inv-actions button,
    .inv-actions a {
        padding: 8px 20px;
        border: 2px solid #fff;
        border-radius: 6px;
        background: transparent;
        color: #fff;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        margin: 0 5px;
    }

    .inv-actions button:hover,
    .inv-actions a:hover {
        background: #fff;
        color: #1B4F72;
    }

    .inv-actions .btn-print {
        background: #fff;
        color: #1B4F72;
    }

    .page {
        position: relative;
        width: 794px;
        min-height: 1123px;
        margin: 40px auto 0;
        background: #ffffff;
        box-shadow: 0 10px 40px rgba(0, 0, 0, .25);
    }

    .content {
        position: relative;
        padding: 50px 40px 0;
    }

    .head-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 22px;
    }

    .head-table td {
        vertical-align: middle;
    }

    .logo-img {
        width: 230px;
        height: auto;
        display: block;
    }

    .logo-name {
        font-size: 26px;
        font-weight: bold;
    }

    .title-cell {
        text-align: right;
    }

    .doc-title {
        font-size: 30px;
        font-weight: bold;
        letter-spacing: 1px;
        text-transform: uppercase;
    }

    .doc-title span {
        border-bottom: 2px dotted #000;
        padding-bottom: 2px;
    }

    .layout-table {
        width: 100%;
        border-collapse: collapse;
    }

    .layout-table>tbody>tr>td {
        vertical-align: top;
    }

    .col-left {
        width: 50%;
        padding-right: 18px;
    }

    .col-right {
        width: 50%;
        padding-left: 18px;
    }

    .text-right {
        text-align: right;
    }

    .text-center {
        text-align: center;
    }

    .fw-bold {
        font-weight: bold;
    }

    .line {
        line-height: 1.5;
    }

    .section-head {
        font-weight: bold;
        text-transform: uppercase;
        font-size: 13px;
        border-bottom: 1px solid #000;
        padding-bottom: 3px;
        margin-bottom: 6px;
    }

    .table-items {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    .table-items th {
        background-color: #4a4a4a;
        color: #fff;
        text-align: center;
        font-weight: bold;
        font-size: 13px;
        padding: 6px 5px;
        border: 1px solid #000;
    }

    .table-items td {
        padding: 5px 8px;
        border: 1px solid #000;
        font-size: 13px;
        vertical-align: top;
    }

    .variant-row {
        font-size: 11px;
        color: #444;
        padding-top: 2px;
    }

    .variant-name {
        display: inline-block;
        min-width: 80px;
    }

    .sum-table {
        width: 100%;
        border-collapse: collapse;
    }

    .sum-table td {
        padding: 3px 0;
        font-size: 13px;
    }

    .sum-table .lbl {
        text-align: right;
        text-transform: uppercase;
        padding-right: 18px;
    }

    .sum-table .val {
        text-align: right;
        width: 110px;
    }

    .sum-table .thin td {
        border-bottom: 1px solid #000;
    }

    .sum-table .thick td {
        border-bottom: 2px solid #000;
    }

    .sum-table .grand td {
        font-weight: bold;
    }

    .due-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 8px;
    }

    .due-table td {
        padding: 2px 0;
        font-size: 13px;
    }

    .due-table .val {
        text-align: right;
    }

    .due-table .total-due td {
        border-top: 1px solid #000;
        font-weight: bold;
        padding-top: 4px;
    }

    .notes-block {
        font-size: 12px;
        color: #333;
        margin-top: 14px;
        line-height: 1.5;
    }

    .sign-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 100px;
    }

    .sign-table td {
        vertical-align: bottom;
    }

    .signature-mark {
        border-top: 1px solid #000;
        width: 170px;
        text-align: center;
        display: inline-block;
        padding-top: 3px;
        font-size: 13px;
    }

    .sheet {
        margin: 0 auto 32px;
    }

    .sheet .content {
        padding-top: 50px;
    }

    @if ($isPdf ?? false)
        /* ---------- DomPDF (downloaded PDF) ----------
     DomPDF paginates natively; keep a small bottom margin so content
     never touches the page edge. */
        @page {
            margin: 0 0 12mm 0;
        }

        body {
            background: #fff;
            margin: 0;
            padding: 0;
        }

        .page {
            width: auto;
            min-height: 0;
            margin: 0;
            box-shadow: none;
        }

        .table-items tr,
        .sign-table,
        .notes-block {
            page-break-inside: avoid;
        }
    @endif

    @media print {
        @page {
            size: A4;
            margin: 0;
        }

        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        body {
            background: #fff;
            padding: 0 !important;
            margin: 0 !important;
        }

        .inv-actions {
            display: none !important;
        }

        .phpdebugbar {
            display: none !important;
        }

        .page {
            box-shadow: none;
            margin: 0;
            width: 210mm;
            min-height: 297mm;
        }

        .sign-table,
        .notes-block {
            page-break-inside: avoid;
        }

        .table-items tr {
            page-break-inside: avoid;
        }

        .sheet {
            margin: 0 !important;
            page-break-after: always;
        }

        .sheet:last-child {
            page-break-after: auto;
        }
    }
</style>
