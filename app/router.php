<?php
/**
 * Main Router
 *
 * tambah router untuk API disini
 * end point dibuat group untuk setiap object API nya supaya rapi
 *
 * API Docs otomatis tergenerate dengan format berikut:
 *
 * setArguments(Array)
 *
 * endpoint = end point api access (contoh: /api/user/login)
 * params = array index -> nama variable parameter, array value -> keterangan (bisa html)
 *
 */

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;


// index utama tampilkan versi API
$app->get('/', function (Request $request, Response $response) {
    $newResponse = $response->withJson( array( 'app_name' => 'E-ORDER API', 'version' => '1.0' ) );
    return $newResponse;
})->setName('/')->setArguments(array('endpoint' => '/', 'params' => 'none'));

// untuk generate halaman API Documentation
$app->get('/docs', function (Request $request, Response $response) {
        $routes = RouteDumper::getAllRoutes($this);
        return $this->renderer->render($response, "docs.php", [
            'title' => 'BIBEX API Documentation',
            'routes' => $routes,
            'request' => $request
        ]);

})->setName('API Docs')->setArguments(array('endpoint' => '/docs', 'params' => 'none'));

//get FAQ
$app->group('/faq', function () use ($app) {
    $app->get('/view', function (Request $request, Response $response) {
            return $this->renderer->render($response, "faq.php", [
                'title' => 'FAQ'
            ]);
    })->setName('FAQ')->setArguments(array('endpoint' => '/view_faq', 'params' => 'none'));

    $app->get('', '\Controller\FaqController::getAll')
        ->setName('Get Setting By Alias')
        ->setArguments(array('endpoint' => '/faq',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

    $app->get('detail/{id}', '\Controller\FaqController::getDetail')
        ->setName('Get Setting By Alias')
        ->setArguments(array('endpoint' => '/faq',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

});

// ambil settingan aplikasi
$app->group('/settings', function () use ($app) {
    $app->get('/all', '\Controller\SettingController::getAll')
        ->setName('Get All Settings')
        ->setArguments(array('endpoint' => '/settings/all',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

    $app->get('/{alias}', '\Controller\SettingController::getbyAlias')
        ->setName('Get Setting By Alias')
        ->setArguments(array('endpoint' => '/settings/{alias}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));
});

//hadiah
$app->group('/hadiah', function () use ($app) {
    $app->get('/detail/{hadiah_id}', '\Controller\HadiahController::getHadiahById')
        ->setName('Get All Settings')
        ->setArguments(array('endpoint' => '/admin/update_token',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

    $app->get('', '\Controller\HadiahController::getAllHadiah')
        ->setName('Get All Settings')
        ->setArguments(array('endpoint' => '/admin/update_token',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

	   $app->post('/ambil_hadiah', '\Controller\HadiahController::ambilHadiah')
        ->setName('Get All Settings')
        ->setArguments(array('endpoint' => '/admin/update_token',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));


	   $app->get('/get_penukaran/{id}', '\Controller\HadiahController::getPenukaranById')
        ->setName('Get All Settings')
        ->setArguments(array('endpoint' => '/admin/update_token',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

    $app->get('/get_riwayat_penukaran/{user_id}', '\Controller\HadiahController::getRiwayatPenukaran')
        ->setName('Get All Settings')
        ->setArguments(array('endpoint' => '/admin/update_token',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));
});

//etalase
$app->group('/etalase', function () use ($app) {
    $app->get('/get_all_by_toko/{toko_id}', '\Controller\EtalaseController::getAllByToko')
        ->setName('Get All Settings')
        ->setArguments(array('endpoint' => '/settings/all',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));
});

//ekspedisi
$app->group('/ekspedisi', function () use ($app) {
    $app->get('/{toko_id}', '\Controller\EkspedisiController::get')
        ->setName('Get All Settings')
        ->setArguments(array('endpoint' => '/settings/all',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

    $app->get('/tabung_galon/get', '\Controller\EkspedisiController::tabungGalon')
        ->setName('Get All Settings')
        ->setArguments(array('endpoint' => '/settings/all',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

});

//flashsale
$app->group('/flashsale', function () use ($app) {
    $app->get('/get_5/{kota_id}', '\Controller\FlashsaleController::get5')
        ->setName('Get All Settings')
        ->setArguments(array('endpoint' => '/settings/all',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

    $app->get('/get_by_id/{id}', '\Controller\FlashsaleController::getByFlashsaleId')
        ->setName('Get All Settings')
        ->setArguments(array('endpoint' => '/settings/all',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));
});

//voucher
$app->group('/voucher', function () use ($app) {
    $app->post('/check', '\Controller\VoucherController::cekVoucher')
        ->setName('Get All Settings')
        ->setArguments(array('endpoint' => '/settings/all',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));
});

//mainkategori
$app->group('/main_kategori', function () use ($app) {
    $app->get('', '\Controller\MainKategoriController::getMainKategori')
        ->setName('Get All Settings')
        ->setArguments(array('endpoint' => '/settings/all',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

    $app->get('/only_penjualan', '\Controller\MainKategoriController::getMainKategoriOnlyPenjualan')
        ->setName('Get All Settings')
        ->setArguments(array('endpoint' => '/settings/all',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

    $app->get('/only_jasa', '\Controller\MainKategoriController::getMainKategoriOnlyJasa')
        ->setName('Get All Settings')
        ->setArguments(array('endpoint' => '/settings/all',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

});

//tabunggalon
$app->group('/tabung_galon', function () use ($app) {
    $app->post('/get_list_outlet', '\Controller\TabungGalonController::getListOutlet')
        ->setName('Input Code')
        ->setArguments(array('endpoint' => '/order',
            'params' => array(
                'phone' => 'phone',
    )));

    $app->get('/galon/{toko_id}', '\Controller\TabungGalonController::getGalonByToko')
        ->setName('Input Code')
        ->setArguments(array('endpoint' => '/order',
            'params' => array(
                'phone' => 'phone',
    )));

    $app->get('/gas/{toko_id}', '\Controller\TabungGalonController::getGasByToko')
        ->setName('Input Code')
        ->setArguments(array('endpoint' => '/order',
            'params' => array(
                'phone' => 'phone',
    )));

    $app->get('/history/{id}', '\Controller\TabungGalonController::getHistory')
        ->setName('Input Code')
        ->setArguments(array('endpoint' => '/order',
            'params' => array(
                'phone' => 'phone',
    )));

    $app->post('/order', '\Controller\TabungGalonController::order')
        ->setName('Input Code')
        ->setArguments(array('endpoint' => '/order',
            'params' => array(
                'phone' => 'phone',
    )));

    $app->get('/history_bisnis/{id}/{toko_id}', '\Controller\TabungGalonController::historyBisnisById')
        ->setName('Input Code')
        ->setArguments(array('endpoint' => '/order',
            'params' => array(
                'phone' => 'phone',
    )));

});


//laundry
$app->group('/laundry', function () use ($app) {
    $app->post('/get_list_outlet', '\Controller\LaundryController::getListOutlet')
        ->setName('Input Code')
        ->setArguments(array('endpoint' => '/order',
            'params' => array(
                'phone' => 'phone',
    )));

    $app->post('/get_list_outlet_2', '\Controller\LaundryController::getListOutlet2')
        ->setName('Input Code')
        ->setArguments(array('endpoint' => '/order',
            'params' => array(
                'phone' => 'phone',
    )));

    $app->get('/cek_poin/{user_id}', '\Controller\LaundryController::cekPoin')
        ->setName('Get history by user')
        ->setArguments(array('endpoint' => '/user/{user_id}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

    $app->post('/change_status_finish', '\Controller\LaundryController::changeStatusFinish')
        ->setName('Input Code')
        ->setArguments(array('endpoint' => '/order',
            'params' => array(
                'phone' => 'phone',
    )));

    $app->post('/order', '\Controller\LaundryController::order')
        ->setName('Input Code')
        ->setArguments(array('endpoint' => '/order',
            'params' => array(
                'phone' => 'phone',
    )));

    $app->get('/history/{id}', '\Controller\LaundryController::historyById')
        ->setName('Input Code')
        ->setArguments(array('endpoint' => '/order',
            'params' => array(
                'phone' => 'phone',
    )));

	$app->get('/layanan', '\Controller\LaundryController::getLayanan')
        ->setName('Input Code')
        ->setArguments(array('endpoint' => '/order',
            'params' => array(
                'phone' => 'phone',
    )));

	$app->get('/satuan/{toko_id}', '\Controller\LaundryController::getSatuan')
        ->setName('Input Code')
        ->setArguments(array('endpoint' => '/order',
            'params' => array(
                'phone' => 'phone',
    )));

	$app->get('/tipe_cuci', '\Controller\LaundryController::getTipeCuci')
        ->setName('Input Code')
        ->setArguments(array('endpoint' => '/order',
            'params' => array(
                'phone' => 'phone',
    )));

    $app->get('/tipe_cuci_by_satuan/{satuan_id}', '\Controller\LaundryController::getTipeCuciBySatuanId')
          ->setName('Input Code')
          ->setArguments(array('endpoint' => '/order',
              'params' => array(
                  'phone' => 'phone',
      )));

	$app->post('/cek_harga_satuan', '\Controller\LaundryController::cekHargaSatuan')
        ->setName('Input Code')
        ->setArguments(array('endpoint' => '/order',
            'params' => array(
                'phone' => 'phone',
    )));

	$app->get('/get_harga_kiloan/{toko_id}', '\Controller\LaundryController::getHargaKiloan')
        ->setName('Input Code')
        ->setArguments(array('endpoint' => '/order',
            'params' => array(
                'phone' => 'phone',
    )));

	$app->get('/get_laundry_position', '\Controller\LaundryController::getPosition')
        ->setName('Input Code')
        ->setArguments(array('endpoint' => '/order',
            'params' => array(
                'phone' => 'phone',
    )));

    $app->get('/get_times_and_duration', '\Controller\LaundryController::getTimesAndDuration')
        ->setName('Input Code')
        ->setArguments(array('endpoint' => '/order',
            'params' => array(
                'phone' => 'phone',
    )));
});

// ambil settingan aplikasi
$app->group('/admin', function () use ($app) {
    $app->post('/update_token', '\Controller\AdminController::save')
        ->setName('Get All Settings')
        ->setArguments(array('endpoint' => '/admin/update_token',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

});

//master
$app->group('/master', function () use ($app) {
    $app->get('/kota', '\Controller\MasterController::getKota')
        ->setName('Get All Settings')
        ->setArguments(array('endpoint' => '/admin/update_token',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

});

//kategori toko
$app->group('/kategori_toko', function () use ($app) {
    $app->get('', '\Controller\KategoriTokoController::getAll')
        ->setName('Get All Toko')
        ->setArguments(array('endpoint' => '/toko',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));
});

//favorite
$app->group('/favorite', function () use ($app) {
    $app->get('/toko/{user_id}', '\Controller\FavoriteController::getToko')
        ->setName('Get All Toko')
        ->setArguments(array('endpoint' => '/toko',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

    $app->get('/barang/{user_id}', '\Controller\FavoriteController::getBarang')
        ->setName('Get All Toko')
        ->setArguments(array('endpoint' => '/toko',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

    $app->post('/set', '\Controller\FavoriteController::setFavorite')
         ->setName('Get All Toko')
         ->setArguments(array('endpoint' => '/toko/{kat_id}',
             'output' => '
             <p>Success: <br><small><i>Object Settings Data</i></small></p>
             <p>Failed: <br><small>{"status":false}</small></p>'
     ));
});

//toko
$app->group('/toko', function () use ($app) {
    $app->get('/{kat_id}/user/{user_id}', '\Controller\TokoController::getAll')
        ->setName('Get All Toko')
        ->setArguments(array('endpoint' => '/toko/{kat_id}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

    $app->get('/detail/{toko_id}', '\Controller\TokoController::getDetail')
        ->setName('Get All Toko')
        ->setArguments(array('endpoint' => '/toko/{kat_id}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

     $app->get('/get_all_by_pasar/{pasar_id}/{user_id}', '\Controller\TokoController::getAllByPasar')
        ->setName('Get All Toko')
        ->setArguments(array('endpoint' => '/toko/{kat_id}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

	 $app->get('/search_toko/{key}/{type}/user/{user_id}', '\Controller\TokoController::searchToko')
        ->setName('Get All Toko')
        ->setArguments(array('endpoint' => '/toko/{kat_id}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

    $app->get('/get_toko_by_main_kat/{main_kategori_id}', '\Controller\TokoController::getTokoByMainkatNew')
         ->setName('Get All Toko')
         ->setArguments(array('endpoint' => '/toko/{kat_id}',
             'output' => '
             <p>Success: <br><small><i>Object Settings Data</i></small></p>
             <p>Failed: <br><small>{"status":false}</small></p>'
     ));

     $app->get('/{id}/{user_id}', '\Controller\TokoController::getById')
          ->setName('Get All Toko')
          ->setArguments(array('endpoint' => '/toko/{kat_id}',
              'output' => '
              <p>Success: <br><small><i>Object Settings Data</i></small></p>
              <p>Failed: <br><small>{"status":false}</small></p>'
     ));

     $app->get('/laundry/get_setting_time/{id}', '\Controller\TokoController::getSettingTime')
        ->setName('Input Code')
        ->setArguments(array('endpoint' => '/credit/input_code',
            'params' => array(
                'phone' => 'phone',
    )));

    $app->post('/tabunggalon/get_outlet_terdekat', '\Controller\TokoController::getTokoTabungGalonTedekat')
       ->setName('Input Code')
       ->setArguments(array('endpoint' => '/credit/input_code',
           'params' => array(
               'phone' => 'phone',
   )));


});

//bisnis
$app->group('/bisnis', function () use ($app) {
    $app->get('/dashboard/{toko_id}', '\Controller\BisnisController::getDashboard')
        ->setName('Get All Toko')
        ->setArguments(array('endpoint' => '/toko/{kat_id}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

    $app->post('/ubah_password', '\Controller\BisnisController::ubahPassword')
        ->setName('Get All Toko')
        ->setArguments(array('endpoint' => '/toko/{kat_id}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

    $app->get('/flashsale/{toko_id}', '\Controller\BisnisController::getFlashsale')
        ->setName('Get All Toko')
        ->setArguments(array('endpoint' => '/toko/{kat_id}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

      $app->get('/flashsale/get_kat_etalase/{toko_id}', '\Controller\BisnisController::getKategoriEtalase')
        ->setName('Get All Toko')
        ->setArguments(array('endpoint' => '/toko/{kat_id}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

    $app->post('/flashsale/input_produk_flashsale', '\Controller\BisnisController::inputProdukFlashsale')
        ->setName('Get All Toko')
        ->setArguments(array('endpoint' => '/toko/{kat_id}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

    $app->post('/flashsale/delete_produk_flashsale', '\Controller\BisnisController::deleteProdukFlashsale')
        ->setName('Get All Toko')
        ->setArguments(array('endpoint' => '/toko/{kat_id}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

     $app->post('/flashsale/barang', '\Controller\BisnisController::getBarangForFlashsale')
        ->setName('Get All Toko')
        ->setArguments(array('endpoint' => '/toko/{kat_id}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

    $app->post('/flashsale/barang_after_input', '\Controller\BisnisController::getBarangAfterInputFlashsale')
        ->setName('Get All Toko')
        ->setArguments(array('endpoint' => '/toko/{kat_id}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

    $app->get('/flashsale_detail/{id}', '\Controller\BisnisController::getFlashsaleById')
        ->setName('Get All Toko')
        ->setArguments(array('endpoint' => '/toko/{kat_id}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

    $app->get('/history_topup_toko/{toko_id}', '\Controller\BisnisController::getHistoryTopup')
        ->setName('Get All Toko')
        ->setArguments(array('endpoint' => '/toko/{kat_id}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

    $app->get('/update_setting_ekspedisi/{ekspedisi_id}/{toko_id}/{visible}', '\Controller\BisnisController::updateSettingEkspedisi')
        ->setName('Get All Toko')
        ->setArguments(array('endpoint' => '/toko/{kat_id}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

    $app->get('/get_ekspedisi/{toko_id}', '\Controller\BisnisController::getEkspedisi')
        ->setName('Get All Toko')
        ->setArguments(array('endpoint' => '/toko/{kat_id}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));


	$app->post('/change_status/{id}/{status}', '\Controller\BisnisController::changeStatus')
        ->setName('Get All Toko')
        ->setArguments(array('endpoint' => '/toko/{kat_id}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

	$app->get('/orders/{toko_id}/{status}', '\Controller\BisnisController::getOrderByStatus')
        ->setName('Get All Toko')
        ->setArguments(array('endpoint' => '/toko/{kat_id}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

    $app->get('/orders_galon_gas/{toko_id}/{status}', '\Controller\BisnisController::getOrderGalonGasByStatus')
          ->setName('Get All Toko')
          ->setArguments(array('endpoint' => '/toko/{kat_id}',
              'output' => '
              <p>Success: <br><small><i>Object Settings Data</i></small></p>
              <p>Failed: <br><small>{"status":false}</small></p>'
      ));

	$app->post('/report/{toko_id}', '\Controller\BisnisController::getReport')
        ->setName('Get All Toko')
        ->setArguments(array('endpoint' => '/toko/{kat_id}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

	$app->get('/setting/{toko_id}', '\Controller\BisnisController::getSetting')
        ->setName('Get All Toko')
        ->setArguments(array('endpoint' => '/toko/{kat_id}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

	$app->post('/update_setting/{toko_id}', '\Controller\BisnisController::updateSetting')
        ->setName('Get All Toko')
        ->setArguments(array('endpoint' => '/toko/{kat_id}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

    $app->get('/get_etalase/{toko_id}', '\Controller\BisnisController::getEtalase')
          ->setName('Get All Toko')
          ->setArguments(array('endpoint' => '/toko/{kat_id}',
              'output' => '
              <p>Success: <br><small><i>Object Settings Data</i></small></p>
              <p>Failed: <br><small>{"status":false}</small></p>'
      ));

      $app->post('/update_etalase', '\Controller\BisnisController::updateEtalase')
            ->setName('Get All Toko')
            ->setArguments(array('endpoint' => '/toko/{kat_id}',
                'output' => '
                <p>Success: <br><small><i>Object Settings Data</i></small></p>
                <p>Failed: <br><small>{"status":false}</small></p>'
        ));

    $app->get('/get_voucher/{toko_id}', '\Controller\BisnisController::getVoucher')
          ->setName('Get All Toko')
          ->setArguments(array('endpoint' => '/toko/{kat_id}',
              'output' => '
              <p>Success: <br><small><i>Object Settings Data</i></small></p>
              <p>Failed: <br><small>{"status":false}</small></p>'
      ));

      $app->get('/get_history_voucher/{toko_id}', '\Controller\BisnisController::getHistoryVoucher')
            ->setName('Get All Toko')
            ->setArguments(array('endpoint' => '/toko/{kat_id}',
                'output' => '
                <p>Success: <br><small><i>Object Settings Data</i></small></p>
                <p>Failed: <br><small>{"status":false}</small></p>'
        ));

      $app->post('/update_voucher', '\Controller\BisnisController::updateVoucher')
            ->setName('Get All Toko')
            ->setArguments(array('endpoint' => '/toko/{kat_id}',
                'output' => '
                <p>Success: <br><small><i>Object Settings Data</i></small></p>
                <p>Failed: <br><small>{"status":false}</small></p>'
        ));

});

//kategori barang
$app->group('/kategori_barang', function () use ($app) {
    $app->get('/page/{current_page}/{type_shop}', '\Controller\KategoriBarangController::getAllByTypeShop')
        ->setName('Get kategoi barang by id')
        ->setArguments(array('endpoint' => '/kategori_barang/page/{current_page}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

    $app->get('/get_by_main_kat/{main_kat_id}', '\Controller\KategoriBarangController::getKatByMainKat')
        ->setName('Get kategoi barang by id')
        ->setArguments(array('endpoint' => '/kategori_barang/page/{current_page}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

	 $app->get('/page/{current_page}', '\Controller\KategoriBarangController::getAll')
        ->setName('Get kategoi barang by id')
        ->setArguments(array('endpoint' => '/kategori_barang/page/{current_page}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

	 $app->get('/get_kategori_by_toko/{toko_id}', '\Controller\KategoriBarangController::getAllByToko')
        ->setName('Get kategoi barang by id')
        ->setArguments(array('endpoint' => '/kategori_barang/page/{current_page}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

    $app->get('/get_all_by_pasar/{pasar_id}', '\Controller\KategoriBarangController::getAllByPasar')
        ->setName('Get kategoi barang by id')
        ->setArguments(array('endpoint' => '/kategori_barang/page/{current_page}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

    $app->get('/makanan', '\Controller\KategoriBarangController::getAllMakanan')
        ->setName('Get kategoi barang by id')
        ->setArguments(array('endpoint' => '/kategori_barang/makanan',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

	$app->get('/produk_khusus/page/{current_page}', '\Controller\KategoriBarangController::getAllProdukKhusus')
        ->setName('Get kategoi barang by id')
        ->setArguments(array('endpoint' => '/kategori_barang/makanan',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

});

//ojek
$app->group('/ojek', function () use ($app) {
	$app->post('/order/{user_id}', '\Controller\OjekController::save')
        ->setName('Order Ojek')
        ->setArguments(array('endpoint' => '/order/{user_id}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

        $app->get('/order/{order_id}', '\Controller\OjekController::getById')
        ->setName('Get Order Ojek by Id')
        ->setArguments(array('endpoint' => '/order/{order_id}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));
});

//courier
$app->group('/courier', function () use ($app) {

	$app->post('/order', '\Controller\CourierController::save')
        ->setName('Order Ojek')
        ->setArguments(array('endpoint' => '/courier/order',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));


    $app->get('/order/{order_id}', '\Controller\CourierController::getById')
            ->setName('Get Order Ojek by Id')
            ->setArguments(array('endpoint' => '/order/{order_id}',
                'output' => '
                <p>Success: <br><small><i>Object Settings Data</i></small></p>
                <p>Failed: <br><small>{"status":false}</small></p>'
        ));

});

//ads
$app->group('/ads', function () use ($app) {
    $app->get('', '\Controller\AdsController::getAll')
        ->setName('Order Ojek')
        ->setArguments(array('endpoint' => '/ads/',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

	 $app->get('/broadcast_history/{user_id}', '\Controller\AdsController::getBroadcastHistory')
        ->setName('Order Ojek')
        ->setArguments(array('endpoint' => '/ads/',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

});

//slider
$app->group('/slider', function () use ($app) {
	$app->get('', '\Controller\SliderController::getAll')
        ->setName('Order Ojek')
        ->setArguments(array('endpoint' => '/slider/',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

	$app->get('/in_wa', '\Controller\SliderController::getInWa')
        ->setName('Order Ojek')
        ->setArguments(array('endpoint' => '/slider/',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

  $app->get('/bisnis', '\Controller\SliderController::getBisnis')
        ->setName('Order Ojek')
        ->setArguments(array('endpoint' => '/slider/',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

});

//tagihan
$app->group('/tagihan', function () use ($app) {
	$app->get('/cek_saldo/{toko_id}', '\Controller\TagihanController::getSinkronSaldo')
        ->setName('Order Ojek')
        ->setArguments(array('endpoint' => '/slider/',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

	$app->get('/history_withdraw/{toko_id}', '\Controller\TagihanController::getHistoryWithdraw')
        ->setName('Order Ojek')
        ->setArguments(array('endpoint' => '/slider/',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

	$app->post('/withdraw', '\Controller\TagihanController::withdraw')
        ->setName('Order Ojek')
        ->setArguments(array('endpoint' => '/slider/',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

});

//search
$app->group('/search', function () use ($app) {
    $app->post('', '\Controller\SearchController::search')
        ->setName('Get All Toko')
        ->setArguments(array('endpoint' => '/toko',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));
});


//barang
$app->group('/barang', function () use ($app) {
    $app->post('/simpan', '\Controller\BarangController::save')
        ->setName('Get Barang by kategori')
        ->setArguments(array('endpoint' => '/barang/{id}/{current_page}/user/{user_id}/khsusus/{is_khusus}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

    $app->get('/produk_utama/{user_id}', '\Controller\BarangController::getProdukUtama')
        ->setName('Get Barang by kategori')
        ->setArguments(array('endpoint' => '/barang/{id}/{current_page}/user/{user_id}/khsusus/{is_khusus}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

    $app->post('/bestseller/{main_kategori_id}/{tipe}/{user_id}', '\Controller\BarangController::getBestSeller')
        ->setName('Get Barang by kategori')
        ->setArguments(array('endpoint' => '/barang/{id}/{current_page}/user/{user_id}/khsusus/{is_khusus}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

    $app->post('/selected/{product_id}/{tipe}/{user_id}', '\Controller\BarangController::getSelectBarangProdukUtama')
        ->setName('Get Barang by kategori')
        ->setArguments(array('endpoint' => '/barang/{id}/{current_page}/user/{user_id}/khsusus/{is_khusus}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

    $app->get('/cek_coord', '\Controller\BarangController::cekCoord')
        ->setName('Get Barang by kategori')
        ->setArguments(array('endpoint' => '/barang/{id}/{current_page}/user/{user_id}/khsusus/{is_khusus}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

    $app->post('/edit_order_barang', '\Controller\BarangController::editOrderBarang')
        ->setName('Get Barang by kategori')
        ->setArguments(array('endpoint' => '/barang/{id}/{current_page}/user/{user_id}/khsusus/{is_khusus}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

    $app->post('/del_order_barang', '\Controller\BarangController::delOrderBarang')
        ->setName('Get Barang by kategori')
        ->setArguments(array('endpoint' => '/barang/{id}/{current_page}/user/{user_id}/khsusus/{is_khusus}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));


    $app->post('/simpan2', '\Controller\BarangController::saveNew')
        ->setName('Get Barang by kategori')
        ->setArguments(array('endpoint' => '/barang/{id}/{current_page}/user/{user_id}/khsusus/{is_khusus}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

    $app->post('/upload_image', '\Controller\BarangController::uploadImage')
        ->setName('Get Barang by kategori')
        ->setArguments(array('endpoint' => '/barang/{id}/{current_page}/user/{user_id}/khsusus/{is_khusus}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

    $app->post('/upload_tmp_image', '\Controller\BarangController::uploadTmpImage')
        ->setName('Get Barang by kategori')
        ->setArguments(array('endpoint' => '/barang/{id}/{current_page}/user/{user_id}/khsusus/{is_khusus}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

    $app->post('/edit_image', '\Controller\BarangController::editImage')
        ->setName('Get Barang by kategori')
        ->setArguments(array('endpoint' => '/barang/{id}/{current_page}/user/{user_id}/khsusus/{is_khusus}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

    $app->post('/delete_image', '\Controller\BarangController::deleteImage')
        ->setName('Get Barang by kategori')
        ->setArguments(array('endpoint' => '/barang/{id}/{current_page}/user/{user_id}/khsusus/{is_khusus}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

    $app->post('/get_by_etalase/{id}/{user_id}', '\Controller\BarangController::getAllBarangByEtalase')
        ->setName('Get Barang by kategori')
        ->setArguments(array('endpoint' => '/barang/{id}/{current_page}/user/{user_id}/khsusus/{is_khusus}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

    $app->get('/{id}/page/{current_page}/user/{user_id}', '\Controller\BarangController::getAllBarang')
        ->setName('Get Barang by kategori')
        ->setArguments(array('endpoint' => '/barang/{id}/{current_page}/user/{user_id}/khsusus/{is_khusus}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

	$app->post('/sort/{id}', '\Controller\BarangController::getAllBarangSortByMainKat')
        ->setName('Get Barang by kategori')
        ->setArguments(array('endpoint' => '/barang/{id}/{current_page}/user/{user_id}/khsusus/{is_khusus}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

    $app->post('/promo_by_pasar/{pasar_id}', '\Controller\BarangController::getAllBarangPromoSort')
        ->setName('Get Barang by kategori')
        ->setArguments(array('endpoint' => '/barang/{id}/{current_page}/user/{user_id}/khsusus/{is_khusus}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

    $app->post('/produk_utama/promo/{product_id}/{user_id}', '\Controller\BarangController::getAllProdukUtamaPromoAll')
        ->setName('Get Barang by kategori')
        ->setArguments(array('endpoint' => '/barang/{id}/{current_page}/user/{user_id}/khsusus/{is_khusus}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

    //promo bytoko
    $app->post('/promo/bytoko/{toko_id}/{user_id}', '\Controller\BarangController::getAllPromoByToko')
        ->setName('Get Barang by kategori')
        ->setArguments(array('endpoint' => '/barang/{id}/{current_page}/user/{user_id}/khsusus/{is_khusus}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

	 $app->post('/sort2/{id}', '\Controller\BarangController::getAllBarangSort2')
        ->setName('Get Barang by kategori')
        ->setArguments(array('endpoint' => '/barang/{id}/{current_page}/user/{user_id}/khsusus/{is_khusus}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

	//ambil barang berdasar toko
	$app->get('/toko/{toko_id}/page/{current_page}/user/{user_id}', '\Controller\BarangController::getAllBarangByToko')
        ->setName('Get Barang by kategori')
        ->setArguments(array('endpoint' => '/barang/{id}/{current_page}/user/{user_id}/khsusus/{is_khusus}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

    //ambil barang berdasar toko dan kategori
    $app->get('/toko/{toko_id}/page/{current_page}/user/{user_id}/kat/{kategori_id}', '\Controller\BarangController::getAllBarangByTokoByKat')
        ->setName('Get Barang by kategori')
        ->setArguments(array('endpoint' => '/barang/{id}/{current_page}/user/{user_id}/khsusus/{is_khusus}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

	//finish order by user
	$app->get('/finish_order/{history_id}/user/{user_id}', '\Controller\BarangController::finishOrder')
        ->setName('Get Barang by kategori')
        ->setArguments(array('endpoint' => '/barang/{id}/{current_page}/user/{user_id}/khsusus/{is_khusus}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

	// dipakai ambil makanan by kategori id
    /*
    $app->get('/makanan/{id}/page/{current_page}/user/{user_id}', '\Controller\BarangController::getAllMakananByKatId') //ambil by kategori id makanan
            ->setName('Get Barang by kategori')
            ->setArguments(array('endpoint' => '/barang/makanan/{id}/page/{current_page}',
                'output' => '
                <p>Success: <br><small><i>Object Settings Data</i></small></p>
                <p>Failed: <br><small>{"status":false}</small></p>'
        ));*/


    $app->get('/makanan/{toko_id}/{kat_id}/page/{current_page}/user/{user_id}', '\Controller\BarangController::getAllMakanan') //ambil by kategori id makanan
            ->setName('Get Barang by kategori')
            ->setArguments(array('endpoint' => '/barang/makanan/{toko_id}/{kat_id}/page/{current_page}',
                'output' => '
                <p>Success: <br><small><i>Object Settings Data</i></small></p>
                <p>Failed: <br><small>{"status":false}</small></p>'
    ));

	//ambil makanan berdasar toko
	$app->get('/makanan/get/toko/{toko_id}/page/{current_page}/user/{user_id}', '\Controller\BarangController::getAllMakananByResto') //ambil by kategori id makanan
            ->setName('Get Barang by kategori')
            ->setArguments(array('endpoint' => '/barang/makanan/{toko_id}/{kat_id}/page/{current_page}',
                'output' => '
                <p>Success: <br><small><i>Object Settings Data</i></small></p>
                <p>Failed: <br><small>{"status":false}</small></p>'
    ));


    $app->get('/detail/{id}/{user_id}', '\Controller\BarangController::getByIdBarang')
        ->setName('Get Barang by kategori')
        ->setArguments(array('endpoint' => '/barang/detail/{id}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

	$app->get('/bisnis/detail/{id}', '\Controller\BarangController::getByIdBarangBisnis')
        ->setName('Get Barang by kategori')
        ->setArguments(array('endpoint' => '/barang/detail/{id}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

	$app->get('/bisnis/delete/{id}', '\Controller\BarangController::delProduk')
        ->setName('Get Barang by kategori')
        ->setArguments(array('endpoint' => '/barang/detail/{id}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

    $app->get('/search_makanan/{keys}/page/{current_page}/user/{user_id}', '\Controller\BarangController::searchMakanan')
        ->setName('Get Barang by id menu')
        ->setArguments(array('endpoint' => '/search/{keys}/page/{current_page}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

    $app->get('/search_barang/{keys}/page/{current_page}/user/{user_id}', '\Controller\BarangController::searchBarang')
        ->setName('Get Barang by id menu')
        ->setArguments(array('endpoint' => '/search_barang/{keys}/page/{current_page}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

	$app->get('/search_barang_khusus/{keys}/page/{current_page}', '\Controller\BarangController::searchBarangKhusus')
        ->setName('Get Barang by id menu')
        ->setArguments(array('endpoint' => '/search_barang/{keys}/page/{current_page}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

    $app->post('/order_barang', '\Controller\BarangController::order')
        ->setName('Order Barang')
        ->setArguments(array('endpoint' => '/barang/order_barang',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

    $app->post('/order_barang_2', '\Controller\BarangController::order2')
        ->setName('Order Barang')
        ->setArguments(array('endpoint' => '/barang/order_barang',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

    $app->get('/history_barang/{order_id}', '\Controller\BarangController::historyBarangById')
        ->setName('Order Barang')
        ->setArguments(array('endpoint' => '/history_barang/{order_id}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

    $app->get('/reorder/{order_id}', '\Controller\BarangController::reorder')
        ->setName('Order Barang')
        ->setArguments(array('endpoint' => '/history_barang/{order_id}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));


    $app->get('/history/{user_id}/{id}', '\Controller\BarangController::historyById')
        ->setName('Get history by id')
        ->setArguments(array('endpoint' => '/barang/history/{user_id}/{id}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

    $app->get('/history_status/{user_id}/{status}', '\Controller\BarangController::historyByStatus')
        ->setName('Get history by status')
        ->setArguments(array('endpoint' => '/history/{user_id}/{status}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

	 $app->post('/get_by_toko/{toko_id}/{kategori_id}', '\Controller\BarangController::getProduk')
        ->setName('Get history by status')
        ->setArguments(array('endpoint' => '/history/{user_id}/{status}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));
});

//history
$app->group('/history', function () use ($app) {
    $app->get('/user/{user_id}', '\Controller\HistoryController::getAllByUser')
        ->setName('Get history by user')
        ->setArguments(array('endpoint' => '/user/{user_id}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

    $app->get('/user/{user_id}/status/{status_id}', '\Controller\HistoryController::getAllByStatus')
        ->setName('Get histoy by user and status')
        ->setArguments(array('endpoint' => '/user/{user_id}/status/{status_id}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));

    $app->get('/user/{user_id}/{id}', '\Controller\HistoryController::getById')
        ->setName('Get histoy by user and id')
        ->setArguments(array('endpoint' => '/user/{user_id}/status/{status_id}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));


});

//pasar
$app->group('/pasar', function () use ($app) {
    $app->get('/get', '\Controller\PasarController::getPasar')
        ->setName('Get history by user')
        ->setArguments(array('endpoint' => '/user/{user_id}',
            'output' => '
            <p>Success: <br><small><i>Object Settings Data</i></small></p>
            <p>Failed: <br><small>{"status":false}</small></p>'
    ));


});

/*token*/
$app->get('/csrf', function(Request $request, Response $response){
  return json_encode(['status' => true,'data' => [ $request->getAttribute('csrf_name') => $request->getAttribute('csrf_value') ] ]  );
})
->setName('Get CSRF')
->setArguments(array('endpoint' => '/user/csrf',
    'params' => 'none'
));

//vendor
$app->group('/vendor', function () use ($app) {

  $app->post('/update_to_cancel_driver_not_found/{id}', '\Controller\VendorController::updateToCancelDriverNotFound')
      ->setName('Get Order Ojek by Id')
      ->setArguments(array('endpoint' => '/order/{order_id}',
          'output' => '
          <p>Success: <br><small><i>Object Settings Data</i></small></p>
          <p>Failed: <br><small>{"status":false}</small></p>'
  ));

  // validasi login
  $app->post('/login', '\Controller\VendorController::login')
      ->setName('Vendor Login')
      ->setArguments(array('endpoint' => '/vendor/login',
          'params' => "none"));

  // ubah oass
  $app->post('/ubah_password', '\Controller\VendorController::ubahPassword')
      ->setName('Vendor Login')
      ->setArguments(array('endpoint' => '/vendor/login',
          'params' => "none"));

  $app->get('/get_saldo/{vendor_id}', '\Controller\SaldoController::getSaldoVendor')
      ->setName('Vendor Login')
      ->setArguments(array('endpoint' => '/vendor/login',
          'params' => "none"));

	$app->post('/login_user_pass', '\Controller\VendorController::loginModePass')
        ->setName('Vendor Login')
        ->setArguments(array('endpoint' => '/vendor/login',
            'params' => "none"));

    $app->get('/review/{id}', '\Controller\VendorController::getReview')
        ->setName('User Login')
        ->setArguments(array('endpoint' => '/review/{id}',
            'params' => array(
                'phone' => 'phone',
    )));

	$app->get('/user_rate/{id}', '\Controller\VendorController::getUserRate')
        ->setName('User Login')
        ->setArguments(array('endpoint' => '/user_rate/{id}',
            'params' => array(
                'phone' => 'phone',
    )));

	$app->post('/pendapatan', '\Controller\VendorController::getPendapatan')
        ->setName('User Login')
        ->setArguments(array('endpoint' => '/pendapatan',
            'params' => array(
                'phone' => 'phone',
    )));

	$app->get('/init_setting', '\Controller\VendorController::initSetting')
        ->setName('Vendor Login')
        ->setArguments(array('endpoint' => '/vendor/init_setting',
            'params' => "none"));

	$app->post('/total_order', '\Controller\VendorController::getTotalOrder')
        ->setName('User Login')
        ->setArguments(array('endpoint' => '/total_order',
            'params' => array(
                'phone' => 'phone',
    )));

	$app->post('/logout', '\Controller\VendorController::logout')
        ->setName('User Login')
        ->setArguments(array('endpoint' => '/logout',
            'params' => array(
                'phone' => 'phone',
    )));

    $app->get('/info', '\Controller\VendorController::getInfo')
        ->setName('User Login')
        ->setArguments(array('endpoint' => '/info',
            'params' => array(
                'phone' => 'phone',
    )));

    $app->get('/order/newest/{type_vendor}/{vendor_id}', '\Controller\VendorController::newestOrder2')
        ->setName('order terbaru')
        ->setArguments(array('endpoint' => '/vendor/order/newest',
            'params' => "none"));


    $app->get('/order/history/{vendor_id}/status/{status_id}', '\Controller\VendorController::history')
        ->setName('order histiry')
        ->setArguments(array('endpoint' => '/vendor/order/history/{vendor_id}/{status_id}',
            'params' => "none"));

    $app->post('/order/accept', '\Controller\VendorController::accept')
        ->setName('accepting order')
        ->setArguments(array('endpoint' => '/vendor/order/accept',
            'params' => "none"));

	$app->post('/set_order', '\Controller\VendorController::setOrder')
        ->setName('accepting order')
        ->setArguments(array('endpoint' => '/vendor/set_order',
            'params' => "none"));

     $app->get('/{vendor_id}', '\Controller\VendorController::getById')
        ->setName('accepting order')
        ->setArguments(array('endpoint' => '/vendor/{vendor_id}',
            'params' => "none"));

     $app->post('/cancel_order/{user_id}', '\Controller\VendorController::cancelOrder')
        ->setName('Cancel booking')
        ->setArguments(array('endpoint' => '/vendor/cancel_booking/{user_id}',
            'params' => "none"));

    $app->post('/finish_order', '\Controller\VendorController::finishOrder')
        ->setName('Finish booking')
        ->setArguments(array('endpoint' => '/vendor/finish_order',
            'params' => "none"));

	$app->post('/get_rating', '\Controller\VendorController::getRating')
        ->setName('Finish booking')
        ->setArguments(array('endpoint' => '/vendor/get_rating',
            'params' => "none"));


    $app->post('/update_position', '\Controller\VendorController::updateLastPosition')
        ->setName('Finish booking')
        ->setArguments(array('endpoint' => '/vendor/update_position',
            'params' => "none"));

    $app->get('/get_position/{vendor_id}/{type_vendor}', '\Controller\VendorController::getVendorPosition')
        ->setName('Finish booking')
        ->setArguments(array('endpoint' => '/vendor/get_position/{vendor_id}/{type_vendor}',
            'params' => "none"));

    $app->post('/get_radius/{type_vendor}', '\Controller\VendorController::getRadius')
        ->setName('Finish booking')
        ->setArguments(array('endpoint' => '/vendor/get_position/{type_vendor}',
            'params' => "none"));

    $app->get('/reject_pijat/{order_id}', '\Controller\PijatController::rejectByVendor')
        ->setName('Finish booking')
        ->setArguments(array('endpoint' => '/vendor/get_position/{type_vendor}',
            'params' => "none"));

	$app->get('/status/{vendor_id}/{status}', '\Controller\VendorController::changeStatus')
        ->setName('Finish booking')
        ->setArguments(array('endpoint' => '/vendor/status/{vendor_id}/{status}',
            'params' => "none"));


});


//credit
$app->group('/credit', function () use ($app) {
    $app->post('/input_code', '\Controller\CreditController::inputCode')
        ->setName('Input Code')
        ->setArguments(array('endpoint' => '/credit/input_code',
            'params' => array(
                'phone' => 'phone',
    )));
});

//user
$app->group('/user', function () use ($app) {

    $app->post('/saran', '\Controller\UserController::saveSaran')
     ->setName('User Login')
     ->setArguments(array('endpoint' => '/check/check_new_version',
         'params' => [] ));

     $app->post('/update_photo_profile', '\Controller\UserController::updatePhotoProfile')
      ->setName('User Login')
      ->setArguments(array('endpoint' => '/check/check_new_version',
          'params' => [] ));


     $app->post('/update_photo_rumah', '\Controller\UserController::updatePhotoRumah')
      ->setName('User Login')
      ->setArguments(array('endpoint' => '/check/check_new_version',
          'params' => [] ));


   $app->post('/update_profile', '\Controller\UserController::updateProfile')
    ->setName('User Login')
    ->setArguments(array('endpoint' => '/check/check_new_version',
        'params' => [] ));

    $app->get('/alamat/{user_id}', '\Controller\UserController::getAlamat')
      ->setName('User Login')
      ->setArguments(array('endpoint' => '/check/check_new_version',
          'params' => [] ));

    $app->post('/alamat/add', '\Controller\UserController::addAlamat')
      ->setName('User Login')
      ->setArguments(array('endpoint' => '/check/check_new_version',
          'params' => [] ));

    $app->get('/alamat/set_utama/{id}/{user_id}', '\Controller\UserController::setAlamatUtama')
      ->setName('User Login')
      ->setArguments(array('endpoint' => '/check/check_new_version',
          'params' => [] ));

    //cek new versioan
	   $app->get('/check_new_version', '\Controller\UserController::checkNewVersion')
        ->setName('User Login')
        ->setArguments(array('endpoint' => '/check/check_new_version',
            'params' => [] ));

    $app->get('/get_poin/{user_id}', '\Controller\PoinController::getUserPoin')
       ->setName('User Login')
       ->setArguments(array('endpoint' => '/check/check_new_version',
           'params' => [] ));

    //snyc setting tarif
    $app->get('/sync_setting', '\Controller\UserController::syncSetting')
        ->setName('User Login')
        ->setArguments(array('endpoint' => '/user/sync_tarif',
            'params' => array(
                'phone' => 'phone',
    )));

     $app->post('/update_position', '\Controller\UserController::updateLastPosition')
        ->setName('Finish booking')
        ->setArguments(array('endpoint' => '/user/update_position',
            'params' => "none"));

    $app->post('/login_mode_user_pass', '\Controller\UserController::loginModeUserPass')
        ->setName('User Login')
        ->setArguments(array('endpoint' => '/user/login',
            'params' => array(
                'phone' => 'phone',
    )));

	$app->post('/update_akun_login', '\Controller\UserController::UpdateAkunLogin')
        ->setName('User Login')
        ->setArguments(array('endpoint' => '/user/login',
            'params' => array(
                'phone' => 'phone',
    )));

    // validasi login
    $app->post('/login', '\Controller\UserController::login')
        ->setName('User Login')
        ->setArguments(array('endpoint' => '/user/login',
            'params' => array(
                'phone' => 'phone',
    )));

    // ambil data user dari ID
    $app->get('/{id}', '\Controller\UserController::getbyId')
        ->setName('Get User by ID')
        ->setArguments(array('endpoint' => '/user/{id}',
    ));

    $app->post('/refresh_token', '\Controller\UserController::refreshToken')
        ->setName('Refresh Token')
        ->setArguments(array('endpoint' => '/user/refresh_token',
    ));

    $app->post('/rate_vendor', '\Controller\UserController::rateVendor')
        ->setName('Rating Vendor')
        ->setArguments(array('endpoint' => '/user/rate_vendor',
    ));

    $app->get('/change_city/{user_id}/{cityId}', '\Controller\UserController::changeCity')
        ->setName('Rating Vendor')
        ->setArguments(array('endpoint' => '/user/rate_vendor',
    ));

    // registrasi user
    $app->post('/register', '\Controller\UserController::register')
        ->setName('User Registration')
        ->setArguments(array('endpoint' => '/user/register',
            'params' => array(
                'phone' => 'phone',
                'email'=> 'email',
                'name' => 'name',
            )
    ));

    //change number
    $app->post('/change_number', '\Controller\UserController::changeNumber')
        ->setName('User Registration')
        ->setArguments(array('endpoint' => '/user/change_number',
            'params' => array(
                'phone' => 'phone',
                'email'=> 'email',
                'name' => 'name',
            )
    ));

     // rating vendor
    $app->post('/give_rating', '\Controller\UserController::giveRating')
        ->setName('Merating vendor')
        ->setArguments(array('endpoint' => '/user/give_rating',
            'params' => 'none'
    ));
});

//saldo
$app->group('/saldo', function () use ($app) {
     $app->post('/topup', '\Controller\SaldoController::topup')
        ->setName('Topup Saldo')
        ->setArguments(array('endpoint' => '/saldo/topup',
            'params' => []));


    $app->post('/topup_vendor', '\Controller\SaldoController::topupVendor')
      ->setName('Topup Saldo')
      ->setArguments(array('endpoint' => '/saldo/topup',
            'params' => []));

    $app->post('/history_user', '\Controller\SaldoController::historyUser')
        ->setName('Topup Saldo')
        ->setArguments(array('endpoint' => '/saldo/history_user',
            'params' => []));

    $app->get('/detail_user/{history_id}', '\Controller\SaldoController::historyUserDetail')
        ->setName('Topup Saldo')
        ->setArguments(array('endpoint' => '/saldo/history_user',
            'params' => []));

	$app->post('/history_vendor', '\Controller\SaldoController::historyVendor')
        ->setName('Topup Saldo')
        ->setArguments(array('endpoint' => '/saldo/history_user',
            'params' => []));

  $app->post('/topup_toko', '\Controller\SaldoController::topupToko')
    ->setName('Topup Saldo')
    ->setArguments(array('endpoint' => '/saldo/topup',
          'params' => []));

  $app->post('/konfirm_toko', '\Controller\SaldoController::konfirmToko')
    ->setName('Topup Saldo')
    ->setArguments(array('endpoint' => '/saldo/topup',
          'params' => []));

  $app->post('/batal_toko', '\Controller\SaldoController::batalToko')
    ->setName('Topup Saldo')
    ->setArguments(array('endpoint' => '/saldo/topup',
          'params' => []));

$app->post('/history_toko', '\Controller\SaldoController::historyToko')
      ->setName('Topup Saldo')
      ->setArguments(array('endpoint' => '/saldo/history_user',
          'params' => []));

  $app->get('/deposit_toko/{toko_id}', '\Controller\SaldoController::depositToko')
        ->setName('Topup Saldo')
        ->setArguments(array('endpoint' => '/saldo/history_user',
            'params' => []));

	$app->post('/batal_user', '\Controller\SaldoController::batalTopupUser')
        ->setName('Topup Saldo')
        ->setArguments(array('endpoint' => '/saldo/history_user',
            'params' => []));

	$app->post('/konfirm_user', '\Controller\SaldoController::konfirmTopupUser')
		->setName('Topup Saldo')
        ->setArguments(array('endpoint' => '/saldo/history_user',
            'params' => []));

	$app->post('/batal_vendor', '\Controller\SaldoController::batalVendor')
        ->setName('Topup Saldo')
        ->setArguments(array('endpoint' => '/saldo/history_user',
            'params' => []));

	$app->post('/konfirm_vendor', '\Controller\SaldoController::konfirmVendor')
		->setName('Topup Saldo')
        ->setArguments(array('endpoint' => '/saldo/history_user',
            'params' => []));

    $app->get('/saldo_user/{user_id}', '\Controller\SaldoController::saldoUser')
		->setName('Topup Saldo')
        ->setArguments(array('endpoint' => '/saldo/history_user',
            'params' => []));

    $app->get('/saldo_vendor/{vendor_id}', '\Controller\SaldoController::saldoVendor')
    ->setName('Topup Saldo')
        ->setArguments(array('endpoint' => '/saldo/history_user',
            'params' => []));
});

//address
$app->group('/gmap',function () use ($app){
  header('Access-Control-Allow-Origin:*', true);
    $app->post('/get_distance', '\Controller\AddressController::getDistanceCoord')
        ->setName('Get Address')
        ->setArguments(array('endpoint' => '/address',
            'params' => 'none'));
});

//jasa
$app->group('/jasa',function () use ($app){
    $app->post('/order', '\Controller\JasaController::order')
        ->setName('Get Address')
        ->setArguments(array('endpoint' => '/address',
            'params' => 'none'));

  $app->get('/history/{id}', '\Controller\JasaController::historyById')
      ->setName('Get Address')
      ->setArguments(array('endpoint' => '/address',
          'params' => 'none'));

    $app->get('/get_all', '\Controller\JasaController::getAll')
        ->setName('Get Address')
        ->setArguments(array('endpoint' => '/address',
            'params' => 'none'));

    $app->get('/gas', '\Controller\JasaController::itemGas')
        ->setName('Get Address')
        ->setArguments(array('endpoint' => '/address',
            'params' => 'none'));

    $app->get('/galon', '\Controller\JasaController::itemGalon')
        ->setName('Get Address')
        ->setArguments(array('endpoint' => '/address',
            'params' => 'none'));

    $app->post('/nearest_outlet', '\Controller\JasaController::getNearestOutlet')
        ->setName('Get Address')
        ->setArguments(array('endpoint' => '/address',
            'params' => 'none'));
});

//chat
$app->group('/chat',function () use ($app){
	header('Access-Control-Allow-Origin: http://156.67.217.74:5311', true);
	//chat on order
	$app->post('/post_chat_order', '\Controller\ChatController::orderChat')
        ->setName('User Login')
        ->setArguments(array('endpoint' => '/user/sync_tarif',
            'params' => array(
                'phone' => 'phone',
    )));


	$app->post('/post_chat_order_kurir_ojek', '\Controller\ChatController::orderChatOjekKurir')
        ->setName('User Login')
        ->setArguments(array('endpoint' => '/user/post_chat_order_kurir_ojek',
            'params' => array(
                'phone' => 'phone',
    )));

	$app->post('/post_chat_order_vendor', '\Controller\ChatController::orderChatVendor')
        ->setName('User Login')
        ->setArguments(array('endpoint' => '/user/post_chat_order_kurir_ojek',
            'params' => array(
                'phone' => 'phone',
    )));

    $app->post('/post', '\Controller\ChatController::save')
        ->setName('Get Address')
        ->setArguments(array('endpoint' => '/chat/post',
            'params' => 'none'));

	$app->get('/set_status/{status}/{user_id}', '\Controller\ChatController::setStatus')
        ->setName('Get Address')
        ->setArguments(array('endpoint' => '/chat/set_status/{status}/{user_id}',
            'params' => 'none'));

	$app->get('/set_status_vendor/{status}/{vendor_id}', '\Controller\ChatController::setStatusVendor')
        ->setName('Get Address')
        ->setArguments(array('endpoint' => '/chat/set_status_vendor/{status}/{vendor_id}',
            'params' => 'none'));

	$app->get('/check_socket/{user_id}', '\Controller\ChatController::checkSocket')
        ->setName('Get Address')
        ->setArguments(array('endpoint' => '/chat/check_socket/{user_id}',
            'params' => 'none'));


	$app->post('/save_socket', '\Controller\ChatController::saveSocket')
        ->setName('Get Address')
        ->setArguments(array('endpoint' => '/chat/save_socket',
            'params' => 'none'));


    $app->get('/row/{id}', '\Controller\ChatController::rowCount')
        ->setName('Get Address')
        ->setArguments(array('endpoint' => '/row/{id}',
            'params' => 'none'));

    $app->get('/get/{id}', '\Controller\ChatController::getChatById')
        ->setName('Get Address')
        ->setArguments(array('endpoint' => '/row/{id}',
            'params' => 'none'));

    $app->get('/get/other/{id}', '\Controller\ChatController::getOtherChatNotInId')
        ->setName('Get Address')
        ->setArguments(array('endpoint' => '/get/other/{id}',
            'params' => 'none'));


    $app->get('/contacts', '\Controller\ChatController::getContacts')
        ->setName('Get Address')
        ->setArguments(array('endpoint' => '/contacts',
            'params' => 'none'));

	$app->get('/contacts_vendor', '\Controller\ChatController::getContactsVendor')
        ->setName('Get Address')
        ->setArguments(array('endpoint' => '/contacts_vendor',
            'params' => 'none'));

    $app->get('/contact/{id}', '\Controller\ChatController::getContact')
        ->setName('Get Address')
        ->setArguments(array('endpoint' => '/contact/{id}',
            'params' => 'none'));

     $app->get('/is_fresh_chat', '\Controller\ChatController::isFreshChat')
        ->setName('Get Address')
        ->setArguments(array('endpoint' => '/is_fresh_chat',
            'params' => 'none'));

	  $app->get('/get_all_user', '\Controller\ChatController::getAllUser')
        ->setName('Get Address')
        ->setArguments(array('endpoint' => '/chat/get_all_user',
            'params' => 'none'));

	  $app->get('/get_all_vendor', '\Controller\ChatController::getAllVendor')
        ->setName('Get Address')
        ->setArguments(array('endpoint' => '/chat/get_all_user',
            'params' => 'none'));

	$app->get('/search_user/{key}', '\Controller\ChatController::searchUser')
        ->setName('Get Address')
        ->setArguments(array('endpoint' => '/chat/search_user/{key}',
            'params' => 'none'));

		$app->get('/search_vendor/{key}', '\Controller\ChatController::searchVendor')
        ->setName('Get Address')
        ->setArguments(array('endpoint' => '/chat/search_user/{key}',
            'params' => 'none'));
});


/* modification end here */
