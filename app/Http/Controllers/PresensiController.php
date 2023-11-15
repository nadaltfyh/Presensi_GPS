<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;


class PresensiController extends Controller
{
    public function create()
    {
        $hariini = date('Y-m-d');
        $nik = Auth::guard('karyawan')->user()->nik;
        $cek = DB::table('presensi')->where('tgl_presensi', $hariini)->where('nik', $nik)->count();
        $lok_kantor = DB::table('konfigurasi_lokasi')->where('id', 1)->first();
        return view('presensi.create', compact('cek','lok_kantor'));
    }

    public function store(Request $request)
    {

        $nik = Auth::guard('karyawan')->user()->nik;
        $tgl_presensi = date('Y-m-d');
        $jam = date('H:i:s');
        $lok_kantor = DB::table('konfigurasi_lokasi')->where('id', 1)->first();
        $lok = explode(",", $lok_kantor->lokasi_kantor);
        $latitudekantor =  $lok[0] ; 
        $longitudekantor = $lok[1];
        $lokasi = $request->lokasi;
        $lokasiuser = explode(",", $lokasi);
        $latitudeuser = $lokasiuser[0];
        $longitudeuser = $lokasiuser[1];
        
        $jarak = $this->distance($latitudekantor, $longitudekantor, $latitudeuser, $longitudeuser);
        $radius = round($jarak['meters']);
        $cek = DB::table('presensi')->where('tgl_presensi', $tgl_presensi)->where('nik', $nik)->count();

        if($cek > 0){
            $ket = 'out';
        } else {
            $ket = 'in';
        }
        $image = $request->image;
        $folderPath = 'public/uploads/absensi/';
        $formatName = $nik . "-" . $tgl_presensi . "-" . $ket;
        $image_parts = explode(';base64', $image);
        $image_base64 = base64_decode($image_parts[1]);
        $fileName = $formatName . ".png";
        $file = $folderPath . $fileName;
       
        
        if($radius > $lok_kantor->radius){
            echo 'error|Maaf Anda Berada diluar Radius, Jarak Anda Adalah ' . $radius . ' meter dari Kantor|radius';
        }else{
        if($cek > 0){
            $data_pulang = [
                'jam_out'=> $jam,
                'foto_out'=> $fileName,
                'lokasi_out'=> $lokasi
            ];
           $update  = DB::table('presensi')->where('tgl_presensi', $tgl_presensi)->where('nik', $nik)->update($data_pulang);
           if($update){
            echo "success|Terimakasih dan Hati Hati di Jalan!|out";
            Storage::put($file, $image_base64);
        } else {
            echo "error|Maaf Gagal Absen, Hubungi Admin|out";
        }
        } else {
            $data = [
                'nik'=> $nik,
                'tgl_presensi'=> $tgl_presensi,
                'jam_in'=> $jam,
                'foto_in'=> $fileName,
                'lokasi_in'=> $lokasi
            ];
            $simpan = DB::table('presensi')->insert($data); 
            if($simpan){
                echo "success|Terimakasih dan Selamat Bekerja!|in";
                Storage::put($file, $image_base64);
            } else {
                echo "error|Maaf Gagal Absen, Hubungi Admin|in";
            }
        }
    }
    
}

    //Menghitung Jarak
    function distance($lat1, $lon1, $lat2, $lon2){
    $theta = $lon1 - $lon2;
    $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
    $dist = acos($dist);
    $dist = rad2deg($dist);
    $miles = $dist * 60 * 1.1515;
    $feet = $miles * 5280;
    $yards = $feet / 3;
    $kilometers = $miles * 1.609344;
    $meters = $kilometers * 1000;
    return compact('meters');
}
public function editprofile()
{
    $nik = Auth::guard('karyawan')->user()->nik;
    $karyawan = DB::table('karyawan')->where('nik', $nik)->first();
    return view('presensi.editprofile', compact('karyawan'));
}

    public function updateprofile(Request $request){
        $nik = Auth::guard('karyawan')->user()->nik;
        $nama_lengkap = $request->nama_lengkap;
        $no_hp = $request->no_hp;
        $password = Hash::make($request->password);
        $karyawan =  DB::table('karyawan')->where('nik', $nik)->first();
        if($request->hasFile('foto')){
            $foto = $nik.".".$request->file('foto')->getClientOriginalExtension();
        }else{
            $foto = $karyawan->foto;
        }
        if(empty($password)){
            $data = [
                'nama_lengkap' => $nama_lengkap,
                'no_hp' => $no_hp,
                'foto' => $foto
            ];
        } else {
            $data = [
                'nama_lengkap' => $nama_lengkap,
                'no_hp' => $no_hp,
                'password' => $password,
                'foto' => $foto
            ];
        }
    
        $update = DB::table('karyawan')->where('nik',$nik)->update($data);
        if($update){
            if($request->hasFile('foto')){
                $folderPath = 'public/uploads/karyawan/'; // Folder untuk menyimpan gambar karyawan
                $request->file('foto')->storeAs($folderPath, $foto); // Menyimpan gambar ke direktori yang ditentukan
            }
            return redirect()->back()->with(['success' => 'Data Berhasil Diupdate']);
        } else {
            return redirect()->back()->with(['error'=> 'Data Gagal']);
        }
    }


    public function createLaporan()
    {
        return view('presensi.laporan');
    }

    public function storeLaporan(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'judul' => 'required|string|max:255',
            'file' => 'required|mimes:pdf,doc,docx,xls,xlsx|max:10240', // Maksimum 10 MB
        ]);
    
        if ($validator->fails()) {
            return redirect('/presensi/laporan')
                        ->withErrors($validator)
                        ->withInput();
        }
    
        $judul = $request->input('judul');
        $file = $request->file('file');
    
        // Simpan informasi laporan ke database
        DB::table('laporan')->insert([
            'judul' => $judul,
            'file_path' => $file->storeAs('public/laporan', $judul . '.' . $file->getClientOriginalExtension()),
            'created_at' => now(), // Tambahkan created_at
            'updated_at' => now(), // Tambahkan updated_at
        ]);
    
        return redirect('/presensi/laporan')->with('success', 'Laporan berhasil diunggah.');
    }
  
    public function izin()
    {
        $nik = Auth::guard('karyawan')->user()->nik;
        $dataizin = DB::table('pengajuan_izin')->where('nik',$nik)->get();
        return view('presensi.izin', compact('dataizin'));
    }

    public function buatizin()
    {
        return view('presensi.buatizin');
    }

    public function storeizin(Request $request){
        $nik = Auth::guard('karyawan')->user()->nik;
        $tgl_izin = $request->tgl_izin;
        $status = $request->status;
        $keterangan = $request->keterangan;
    
        $data = [
            'nik' => $nik,
            'tgl_izin' => $tgl_izin,
            'status' => $status,
            'keterangan' => $keterangan
        ];
    
        $simpan = DB::table('pengajuan_izin')->insert($data);
    
        if($simpan){
            return redirect('/presensi/izin')->with(['success'=>'Data Berhasil Disimpan']);
        } else {
            return redirect('/presensi/izin')->with(['error'=>'Data Gagal Disimpan']);
        }
    }
    
}