<?php
include 'config.php';
if(!isset($_SESSION['user_id'])) header("Location: login.php");
$user_id = $_SESSION['user_id'];

$nama_pembeli = $_POST['nama_pembeli'];
$tanggal = $_POST['tanggal'];
$keterangan = $_POST['keterangan'] ?? '';
$rasa_array = $_POST['rasa'];
$jumlah_array = $_POST['jumlah_porsi'];

if(count($rasa_array) == 0) die("Tidak ada data rasa.");
$detail_items = [];
$total_keseluruhan = 0;
for($i=0; $i<count($rasa_array); $i++){
    $rasa_input = $rasa_array[$i];
    $jml = (int)$jumlah_array[$i];
    if($jml<=0) continue;
    if($rasa_input == 'Acak'){ $opsi=['Gurih','Pedas','BBQ','Rumput Laut']; $rasa=$opsi[array_rand($opsi)]; }
    else $rasa = $rasa_input;
    $subtotal = $jml*1000;
    $total_keseluruhan += $subtotal;
    $detail_items[] = ['rasa'=>$rasa,'jml'=>$jml,'subtotal'=>$subtotal];
}
if(empty($detail_items)) die("Tidak ada item valid.");
$conn->begin_transaction();
try{
    $stmt = $conn->prepare("INSERT INTO pesanan (tanggal, nama_pembeli, user_id, total_harga, keterangan) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssids", $tanggal, $nama_pembeli, $user_id, $total_keseluruhan, $keterangan);
    $stmt->execute();
    $pesanan_id = $stmt->insert_id;
    $stmt->close();
    foreach($detail_items as $item){
        $stmt2 = $conn->prepare("INSERT INTO detail_pesanan (pesanan_id, rasa, jumlah_porsi, subtotal) VALUES (?, ?, ?, ?)");
        $stmt2->bind_param("isid", $pesanan_id, $item['rasa'], $item['jml'], $item['subtotal']);
        $stmt2->execute();
        $stmt2->close();
        $sumber = "Penjualan Malfi - {$item['rasa']} (an. $nama_pembeli)";
        $ket_pemasukan = "Pesanan #$pesanan_id, {$item['jml']} porsi {$item['rasa']}";
        $stmt3 = $conn->prepare("INSERT INTO pemasukan (tanggal, sumber, jumlah, keterangan, pesanan_id) VALUES (?, ?, ?, ?, ?)");
        $stmt3->bind_param("ssdsi", $tanggal, $sumber, $item['subtotal'], $ket_pemasukan, $pesanan_id);
        $stmt3->execute();
        $stmt3->close();
    }
    $conn->commit();
    header("Location: pesanan.php?sukses=1");
    exit;
} catch(Exception $e){
    $conn->rollback();
    die("Gagal menyimpan: ".$e->getMessage());
}
?>