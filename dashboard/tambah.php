<?php

require '../config/database.php';
require '../config/notifikasi.php';
require '../auth/cek_login.php';

$belum_baca = jumlah_notifikasi_belum_dibaca($conn);
$halaman_aktif = 'tambah';
$total_flag = 0;
$qflag = mysqli_query($conn, "SELECT id, nik_peserta, nik_ahli_waris FROM rekomendasi");
if ($qflag) { while ($f = mysqli_fetch_assoc($qflag)) { $np = preg_replace('/\D/','',trim($f['nik_peserta']??'')); $na = preg_replace('/\D/','',trim($f['nik_ahli_waris']??'')); if (($np!=='' && strlen($np)!==16) || ($na!=='' && strlen($na)!==16)) $total_flag++; } }
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Tambah Data Rekomendasi</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="../assets/admin.css" rel="stylesheet">
<style>
    .nik-feedback{font-size:11px;margin-top:5px;color:rgba(255,255,255,.45);line-height:1.3;min-height:13px}
    .nik-feedback.ok{color:#4ade80}
    .nik-feedback.warn{color:#fbbf24}
    .nik-feedback.err{color:#f87171}
</style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="topbar">
    <button class="btn btn-glass" onclick="toggleSidebar()"><i class="bi bi-list fs-5"></i></button>
    <span class="brand-mobile">Tambah Data</span>
    <span></span>
</div>

<div class="content">
<div class="glass-card">
<div class="card-header">
    <h4 class="fw-bold mb-0" style="color:#fff"><i class="bi bi-plus-circle me-2" style="color:var(--blue-soft)"></i>Tambah Data Rekomendasi</h4>
    <div class="small-muted">Lengkapi data peserta &amp; ahli waris di bawah ini</div>
</div>
<div class="card-body">
<form action="simpan.php" method="POST">

<div class="section-title">Data Surat</div>
<div class="row g-3 mb-4">
<div class="col-md-8">
<label class="form-label">Nomor Surat</label>
<input type="text" name="nomor_surat" id="nomor_surat" class="form-control" placeholder="500.15.14.2/..../DTKT/IV/2026 (opsional)">
</div>
<div class="col-md-4">
<label class="form-label">Tanggal Surat</label>
<input type="date" name="tanggal_surat" id="tanggal_surat" class="form-control">
</div>
</div>

<div class="section-title">Data Peserta <span style="color:rgba(255,255,255,.4);font-weight:400;text-transform:none">(Almarhum/Almarhumah)</span></div>
<div class="row g-3 mb-4">
<div class="col-md-6">
<label class="form-label">Nama Lengkap</label>
<input type="text" name="nama_peserta" class="form-control" required>
</div>
<div class="col-md-3">
<label class="form-label">NIK</label>
<input type="text" name="nik_peserta" id="nik_peserta" class="form-control nik-input" maxlength="16" inputmode="numeric" required>
<div id="feedback_nik_peserta" class="nik-feedback"></div>
</div>
<div class="col-md-3">
<label class="form-label">Pekerjaan</label>
<input type="text" name="pekerjaan_peserta" class="form-control">
</div>
<div class="col-12">
<label class="form-label">Alamat</label>
<textarea name="alamat_peserta" class="form-control" placeholder="Alamat lengkap peserta"></textarea>
</div>
<div class="col-md-4">
<label class="form-label">Tanggal Meninggal</label>
<input type="date" name="tanggal_meninggal" class="form-control">
</div>
</div>

<div class="section-title">Data Agama</div>
<div class="row g-3 mb-4">
<div class="col-md-4">
<label class="form-label">Agama</label>
<select name="agama" class="form-select">
<option value="">-- Pilih --</option>
<option value="Islam">Islam</option>
<option value="Kristen Protestan">Kristen Protestan</option>
<option value="Kristen Katolik">Kristen Katolik</option>
<option value="Hindu">Hindu</option>
<option value="Buddha">Buddha</option>
<option value="Konghucu">Konghucu</option>
</select>
</div>
<div class="col-md-4">
<label class="form-label">Denominasi</label>
<input type="text" name="denominasi" class="form-control" placeholder="Contoh: NU, Muhammadiyah, dll">
</div>
</div>

<div class="section-title">Data Ahli Waris</div>
<div class="row g-3 mb-4">
<div class="col-md-6">
<label class="form-label">Nama Lengkap</label>
<input type="text" name="nama_ahli_waris" class="form-control">
</div>
<div class="col-md-3">
<label class="form-label">Hubungan Keluarga</label>
<select name="hubungan_ahli_waris" class="form-select">
<option value="">-- Pilih --</option>
<option value="Suami">Suami</option>
<option value="Istri">Istri</option>
<option value="Anak kandung">Anak kandung</option>
<option value="Orang tua">Orang tua</option>
<option value="Saudara kandung">Saudara kandung</option>
<option value="Lainnya">Lainnya</option>
</select>
</div>
<div class="col-md-3">
<label class="form-label">NIK</label>
<input type="text" name="nik_ahli_waris" id="nik_ahli_waris" class="form-control nik-input" maxlength="16" inputmode="numeric">
<div id="feedback_nik_ahli_waris" class="nik-feedback"></div>
</div>
<div class="col-12">
<label class="form-label">Alamat</label>
<textarea name="alamat_ahli_waris" class="form-control" placeholder="Alamat lengkap ahli waris"></textarea>
</div>
<div class="col-md-4">
<label class="form-label">No. HP</label>
<input type="text" name="no_hp_ahli_waris" class="form-control" placeholder="08xx-xxxx-xxxx">
</div>
</div>

<div class="section-title">Status Pengurusan</div>
<div class="row g-3 mb-4">
<div class="col-md-6">
<label class="form-label">Status</label>
<select name="status" class="form-select">
<option value="Belum Diurus">Belum Diurus</option>
<option value="Sudah Diurus">Sudah Diurus</option>
</select>
</div>
</div>

<div class="d-flex gap-2 pt-2">
<button class="btn-glass solid px-4"><i class="bi bi-check-lg"></i> Simpan</button>
<a href="data.php" class="btn-glass px-4"><i class="bi bi-arrow-left"></i> Kembali</a>
</div>

</form>
</div>
</div>
</div>

<script>
function toggleSidebar(){
    document.getElementById('sidebar').classList.toggle('show');
    document.getElementById('sidebarOverlay').classList.toggle('show');
}

function bulanKeRomawi(n) {
    return ['','I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'][parseInt(n)] || '';
}

function updateRomawi() {
    var tgl = document.getElementById('tanggal_surat');
    if (!tgl.value) return;
    var parts = tgl.value.split('-');
    var romawi = bulanKeRomawi(parts[1]);
    var tahun = parts[0];
    var input = document.getElementById('nomor_surat');
    if (input.value.trim() === '') {
        input.value = '500.15.14.2/    /DTKT/' + romawi + '/' + tahun;
    } else {
        input.value = input.value.replace(/(DTKT\/)[A-Z]+(\/\d{4})/i, '$1' + romawi + '$2');
    }
}

document.getElementById('tanggal_surat').addEventListener('change', updateRomawi);
document.addEventListener('DOMContentLoaded', updateRomawi);

function updateNikFeedback(input){
    var digits = input.value.replace(/\D/g, '');
    var box = document.getElementById('feedback_' + input.name);
    if(!box) return;

    if(digits.length === 0){
        if(input.hasAttribute('required')){
            box.innerHTML = '<i class="bi bi-exclamation-triangle"></i> NIK wajib diisi (16 digit)';
            box.className = 'nik-feedback warn';
        } else {
            box.innerHTML = '<i class="bi bi-info-circle"></i> NIK opsional (16 digit)';
            box.className = 'nik-feedback';
        }
        return;
    }
    if(digits.length < 16){
        box.innerHTML = '<i class="bi bi-exclamation-triangle"></i> Hanya ' + digits.length + ' digit — NIK wajib 16 digit angka!';
        box.className = 'nik-feedback err';
        return;
    }
    if(digits.length > 16){
        box.innerHTML = '<i class="bi bi-exclamation-triangle"></i> ' + digits.length + ' digit — NIK terlalu panjang (maks 16)!';
        box.className = 'nik-feedback err';
        return;
    }
    box.innerHTML = '<i class="bi bi-check-circle"></i> NIK valid (16 digit)';
    box.className = 'nik-feedback ok';
}

function updateAllNikFeedback(){
    document.querySelectorAll('.nik-input').forEach(updateNikFeedback);
}

function initNikValidation(){
    var inputs = document.querySelectorAll('.nik-input');
    var form = inputs.length > 0 ? inputs[0].closest('form') : null;

    inputs.forEach(function(input){
        input.addEventListener('input', function(){
            this.value = this.value.replace(/\D/g, '').slice(0, 16);
            updateNikFeedback(this);
        });
        input.addEventListener('blur', function(){
            updateNikFeedback(this);
        });
    });

    if(form && !form.dataset.nikGuard){
        form.dataset.nikGuard = '1';
        form.addEventListener('submit', function(e){
            var bad = [];
            inputs.forEach(function(inp){
                var digits = inp.value.replace(/\D/g, '');
                if(inp.hasAttribute('required') && digits.length !== 16){
                    bad.push(inp.name.replace(/_/g, ' ').toUpperCase() + ': ' + digits.length + ' digit (harus 16)');
                }
            });
            if(bad.length > 0){
                alert('Perhatian: NIK tidak sesuai regulasi Indonesia (wajib 16 digit angka).\n\n' + bad.join('\n') + '\n\nData tetap akan disimpan dan dicatat ke sistem notifikasi.');
            }
        });
    }
    updateAllNikFeedback();
}

document.addEventListener('DOMContentLoaded', initNikValidation);
</script>
</body>
</html>
