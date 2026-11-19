<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $sheet->name }} — {{ $profile?->business_name ?? 'MaliConnect' }}</title>
    <style>
        @page {
            margin: 32px 34px 46px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: "DejaVu Sans", sans-serif;
            color: #1f2933;
            font-size: 10.5px;
            line-height: 1.5;
        }

        .header {
            width: 100%;
            border-bottom: 3px solid #0a6d46;
            padding-bottom: 14px;
            margin-bottom: 20px;
        }

        .header td {
            vertical-align: top;
        }

        .logo {
            width: 58px;
            height: 58px;
            border-radius: 10px;
            object-fit: cover;
        }

        .logo-placeholder {
            width: 58px;
            height: 58px;
            border-radius: 10px;
            background: #0a6d46;
            color: #ffffff;
            text-align: center;
            line-height: 58px;
            font-size: 22px;
            font-weight: bold;
        }

        .brand-name {
            margin: 0;
            font-size: 19px;
            font-weight: bold;
            color: #0b4731;
        }

        .brand-tagline {
            margin: 2px 0 0;
            color: #667085;
            font-size: 9.5px;
        }

        .brand-contact {
            text-align: right;
            color: #475467;
            font-size: 9.5px;
            line-height: 1.6;
        }

        .sheet-title {
            margin: 0 0 4px;
            color: #0b4731;
            font-size: 21px;
            font-weight: bold;
        }

        .sheet-description {
            margin: 0 0 8px;
            color: #475467;
            font-size: 10.5px;
        }

        .meta {
            margin: 0 0 16px;
            color: #667085;
            font-size: 9.5px;
        }

        .meta strong {
            color: #344054;
        }

        .section-title {
            margin: 18px 0 8px;
            color: #0b4731;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }

        table.items thead th {
            background: #0b4731;
            color: #ffffff;
            text-align: left;
            font-size: 9px;
            font-weight: bold;
            padding: 7px 8px;
            border: 1px solid #0b4731;
        }

        table.items tbody td {
            padding: 8px;
            border: 1px solid #e4e7ec;
            vertical-align: top;
        }

        table.items tbody tr:nth-child(even) td {
            background: #f9fafb;
        }

        .item-name {
            font-weight: bold;
            color: #101828;
            font-size: 10.5px;
        }

        .item-description {
            margin: 3px 0 0;
            color: #667085;
            font-size: 9px;
        }

        .item-detail {
            margin: 3px 0 0;
            color: #475467;
            font-size: 9px;
        }

        .price {
            font-weight: bold;
            color: #0a6d46;
            white-space: nowrap;
        }

        .quote-badge {
            display: inline-block;
            margin-top: 3px;
            padding: 2px 6px;
            border-radius: 4px;
            background: #fef3c7;
            color: #92400e;
            font-size: 8px;
            font-weight: bold;
        }

        .conditions {
            margin-top: 18px;
            padding: 10px 12px;
            border-left: 3px solid #f59e0b;
            background: #fffbeb;
            color: #475467;
            font-size: 9.5px;
            white-space: pre-line;
        }

        .footer {
            position: fixed;
            bottom: -26px;
            left: 0;
            right: 0;
            border-top: 1px solid #e4e7ec;
            padding-top: 6px;
            color: #98a2b3;
            font-size: 8px;
        }

        .footer-right {
            text-align: right;
        }

        .empty {
            padding: 14px;
            border: 1px dashed #d0d5dd;
            color: #667085;
            text-align: center;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td style="width: 58px;">
                @if ($logo)
                    <img src="{{ $logo }}" alt="Logo" class="logo">
                @else
                    <div class="logo-placeholder">{{ mb_substr($profile?->business_name ?? 'M', 0, 1) }}</div>
                @endif
            </td>
            <td style="padding-left: 12px;">
                <p class="brand-name">{{ $profile?->business_name ?? 'MaliConnect' }}</p>
                <p class="brand-tagline">Fiche tarifaire professionnelle · MaliConnect</p>
            </td>
            <td class="brand-contact">
                @if ($profile?->phone)
                    Tél. : {{ $profile->phone }}<br>
                @endif
                @if ($profile?->whatsapp)
                    WhatsApp : {{ $profile->whatsapp }}<br>
                @endif
                @if ($profile?->email)
                    {{ $profile->email }}<br>
                @endif
                @if ($profile?->address)
                    {{ $profile->address }}<br>
                @endif
                {{ $profile?->city }}@if ($profile?->commune) · {{ $profile->commune }}@endif
            </td>
        </tr>
    </table>

    <h1 class="sheet-title">{{ $sheet->name }}</h1>

    @if ($sheet->description)
        <p class="sheet-description">{{ $sheet->description }}</p>
    @endif

    <p class="meta">
        @if ($sheet->category)
            <strong>Catégorie :</strong> {{ $sheet->category->name }} ·
        @endif
        <strong>Devise :</strong> {{ $currencyLabel }} ·
        <strong>Mise à jour :</strong> {{ $sheet->updated_at?->format('d/m/Y') ?? $generatedAt->format('d/m/Y') }}
        @if ($sheet->valid_until)
            · <strong>Valable jusqu’au :</strong> {{ $sheet->valid_until->format('d/m/Y') }}
        @endif
    </p>

    <p class="section-title">Prestations et tarifs</p>

    @if ($sheet->items->isEmpty())
        <p class="empty">Aucune prestation disponible pour le moment.</p>
    @else
        <table class="items">
            <thead>
                <tr>
                    <th style="width: 44%;">Prestation</th>
                    <th style="width: 18%;">Catégorie</th>
                    <th style="width: 22%;">Tarif</th>
                    <th style="width: 16%;">Durée / unité</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($sheet->items as $item)
                    <tr>
                        <td>
                            <div class="item-name">{{ $item->name }}</div>
                            @if ($item->description)
                                <p class="item-description">{{ $item->description }}</p>
                            @endif
                            @if ($item->conditions)
                                <p class="item-detail"><strong>Conditions :</strong> {{ $item->conditions }}</p>
                            @endif
                        </td>
                        <td>{{ $item->category?->name ?? $sheet->category?->name ?? '—' }}</td>
                        <td>
                            <span class="price">{{ $item->formatted_price }}</span>
                            @if ($item->requires_diagnostic)
                                <div class="quote-badge">Diagnostic requis</div>
                            @elseif ($item->isQuote())
                                <div class="quote-badge">Sur devis</div>
                            @endif
                        </td>
                        <td>
                            @if ($item->billing_unit)
                                {{ $item->billing_unit }}<br>
                            @endif
                            {{ $item->formatted_duration ?? '—' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if ($sheet->general_conditions)
        <div class="conditions">
            <strong>Conditions générales</strong><br>
            {{ $sheet->general_conditions }}
        </div>
    @endif

    <div class="footer">
        <table style="width: 100%;">
            <tr>
                <td>Document généré par MaliConnect le {{ $generatedAt->format('d/m/Y à H:i') }}.</td>
                <td class="footer-right">{{ $sheet->name }} · {{ $profile?->business_name }}</td>
            </tr>
        </table>
    </div>
</body>
</html>
