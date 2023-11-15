@extends('layouts.presensi')

@section('header') 
<!-- App Header -->
<div class="appHeader bg-primary text-light">
    <div class="left">
        <a href="javascript:;" class="headerButton goBack">
            <ion-icon name="chevron-back-outline"></ion-icon>
        </a>
    </div>
    <div class="pageTittle">Laporan & Surat</div>
    <div class="right"></div>
</div>
<!-- App Header -->
@endsection

@section('content')
<div class="row" style="margin-top: 70px">
    <div class="col">
        <form action="{{ route('laporan.store') }}" method="post" enctype="multipart/form-data" id="laporanForm">
            @csrf
            <div class="form-group">
                <label for="judul">Judul Laporan</label>
                <input type="text" name="judul" id="judul" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="file">Upload File</label>
                <input type="file" name="file" id="file" class="form-control-file" accept=".pdf,.doc,.docx,.xls,.xlsx" required>
                @error('file')
                    <small class="text-danger form-control">{{ $message }}</small>
                @enderror
            </div>
            <div class="form-group">
                <button type="button" class="btn btn-primary" onclick="submitForm()">
                    <ion-icon name="cloud-upload-outline"></ion-icon> Upload Laporan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('myscript')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@9"></script>

<script>
    function submitForm() {
        // Menangani submit form dengan AJAX
        $.ajax({
            type: 'POST',
            url: '{{ route('laporan.store') }}',
            data: new FormData($('#laporanForm')[0]),
            contentType: false,
            processData: false,
            cache: false,
            success: function (response) {
                // Menangani notifikasi berdasarkan respons dari server
                if (response.success) {
                    showSuccessNotification('Laporan berhasil diunggah.');
                    // Redirect atau lakukan tindakan lain setelah berhasil
                    window.location.href = '/tujuan_setelah_berhasil';
                } else {
                    showErrorNotification('Terjadi kesalahan: ' + response.message);
                }
            },
            error: function (error) {
                // Menangani kesalahan AJAX
                console.log(error);
                showErrorNotification('Terjadi kesalahan saat mengunggah laporan.');
            }
        });
    }
</script>

@endpush
