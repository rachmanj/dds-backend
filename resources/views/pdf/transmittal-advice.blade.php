<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Transmittal Advice - {{ $distribution->distribution_number }}</title>

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/fontawesome-free/css/all.min.css') }}">
    <!-- Theme style -->
    <link rel="stylesheet" href="{{ asset('adminlte/dist/css/adminlte.min.css') }}">
</head>

<body>
    <div class="wrapper">
        <!-- Main content -->
        <section class="invoice">
            <!-- title row -->
            <div class="row">
                <div class="col-12">
                    <table class="table">
                        <tr>
                            <td rowspan="2">
                                <h4>PT Arkananta Apta Pratista</h4>
                            </td>
                            <td rowspan="2">
                                <h3><b>Transmittal Advice</b></h3>
                                <h4>Surat Pengantar Dokumen</h4>
                                <h4>Nomor: {{ $distribution->distribution_number }}</h4>
                            </td>
                            <td class="text-">ARKA/DDS/{{ date('m/Y', strtotime($distribution->created_at)) }}</td>
                        </tr>
                        <tr>
                            <td>{{ $distribution->created_at->format('d-M-Y') }}</td>
                        </tr>
                    </table>
                </div>
                <!-- /.col -->
            </div>
            <!-- info row -->
            <div class="row">
                <div class="col-5">
                    Kepada
                    <address>
                        <strong>{{ $distribution->destinationDepartment->name }}</strong> <br>
                        <strong>{{ $distribution->destinationDepartment->location_code }}</strong><br>
                        {{ $distribution->destinationDepartment->name }}
                    </address>
                </div>
                <div class="col-6">
                    <p>
                    <h5>Date: {{ $distribution->created_at->format('d-M-Y') }}</h5>
                    </p>
                    <p>Type:
                        <span
                            class="badge badge-{{ strtolower($distribution->type->code) == 'normal' ? 'success' : (strtolower($distribution->type->code) == 'urgent' ? 'danger' : 'primary') }}">
                            {{ $distribution->type->name }}
                        </span>
                    </p>
                    <p>Status: {{ ucfirst(str_replace('_', ' ', $distribution->status)) }}</p>
                    <p>From: {{ $distribution->originDepartment->name }}
                        ({{ $distribution->originDepartment->location_code }})</p>
                    <p>Created by: {{ $distribution->creator->name }}</p>
                    @if ($distribution->notes)
                        <p>Notes: {{ $distribution->notes }}</p>
                    @endif
                </div>
            </div>
            <!-- /.row -->

            <!-- Table row -->
            <div class="row">
                <div class="col-12 table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>NO</th>
                                <th>DOCUMENT TYPE</th>
                                <th>DOCUMENT NUMBER</th>
                                <th>DATE</th>
                                <th>DESCRIPTION</th>
                                <th class="text-center">AMOUNT</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($documents as $index => $document)
                                <tr>
                                    <th>{{ $index + 1 }}</th>
                                    <th>{{ $document['type'] }}</th>
                                    <th>{{ $document['number'] }}</th>
                                    <th>{{ $document['date'] }}</th>
                                    <th>{{ $document['description'] }}</th>
                                    <th class="text-right">
                                        @if ($document['amount'])
                                            {{ $document['currency'] }} {{ number_format($document['amount'], 2) }}
                                        @else
                                            -
                                        @endif
                                    </th>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center" style="color: #666;">No documents attached
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <!-- /.col -->
            </div>
            <!-- /.row -->

            <!-- Summary row -->
            <div class="row">
                <div class="col-12">
                    <p><strong>Total Documents:</strong> {{ count($documents) }}</p>
                    @if ($distribution->sender_verified_at)
                        <p><strong>Sender Verified:</strong>
                            {{ $distribution->sender_verified_at->format('d/m/Y H:i') }}</p>
                    @endif
                    @if ($distribution->receiver_verified_at)
                        <p><strong>Receiver Verified:</strong>
                            {{ $distribution->receiver_verified_at->format('d/m/Y H:i') }}</p>
                    @endif
                </div>
            </div>

            <!-- Signature row -->
            <div class="row">
                <div class="col-12">
                    <table class="table">
                        <tr>
                            <th>Sender</th>
                            <th>Acknowledge</th>
                            <th>Receiver</th>
                        </tr>
                        <tr>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td>({{ $distribution->creator->name }})</td>
                            <td>(____________________________________)</td>
                            <td>(____________________________________)</td>
                        </tr>
                        <tr>
                            <td>{{ $distribution->originDepartment->name }}</td>
                            <td></td>
                            <td>{{ $distribution->destinationDepartment->name }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Footer -->
            <div class="row">
                <div class="col-12 text-center" style="font-size: 10px; color: #666; margin-top: 20px;">
                    Generated on {{ $generated_at->format('d F Y \a\t H:i:s') }}
                    @if (isset($generated_by))
                        by {{ $generated_by->name }}
                    @endif
                </div>
            </div>
        </section>
        <!-- /.content -->
    </div>
    <!-- ./wrapper -->
    <!-- Page specific script -->
    <script>
        window.addEventListener("load", window.print());
    </script>
</body>

</html>
