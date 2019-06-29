<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \GuzzleHttp\Psr7;
use Illuminate\Support\Facades\DB;

class MaintenancePageController extends Controller
{
    // トップページの表示
    public function index()
    {
        // レストランデータの取得
        $responseData = self::getRestaurantData(1);

        // API実行エラーの場合
        if ($responseData == "Client error") {
            return redirect()->to('errors/404');
        }

        /* 整形用のデータを作成 */
        // 該当件数
        $totalHitCount = null;
        // 表示件数
        $hitPerPage = null;
        // 表示ページ
        $pageOffset = null;
        // 飲食店情報配列
        $restaurantArray = null;
        // 画面出力用データ
        $viewData = null;
        // ページ数
        $pageCount = 1;

        // 配列のkeyによってデータを振り分ける
        foreach ($responseData as $responseKey => $apiData) {
            switch ($responseKey) {
                case 'total_hit_count':
                    $totalHitCount = $apiData;
                    break;
                case 'hit_per_page':
                    $hitPerPage = $apiData;
                    break;
                case 'page_offset':
                    $pageOffset = $apiData;
                    break;
                case 'rest':
                    $restaurantArray = $apiData;
                    $viewData = json_encode($restaurantArray);
                    break;
                default:
                    break;
            }
        }

        // 1回の実行で取得出来る数よりも合計のデータ数が多い場合
        if ($totalHitCount > $hitPerPage) {
            $pageCount = self::getPageCount($totalHitCount, $hitPerPage);
        }

        return view('maintenance.index')->with('viewData', $viewData)->with('pageOffset', $pageOffset)->with('pageCount', $pageCount);
    }

    // ページリクエスト処理
    public function pageIndex($count)
    {
        // パラメーターチェック
        self::countCheck($count);

        // レストランデータの取得
        $responseData = self::getRestaurantData($count);

        // API実行エラーの場合
        if ($responseData == "Client error") {
            return view('errors.404');
        }

        /* 整形用のデータを作成 */
        // 該当件数
        $totalHitCount = null;
        // 表示件数
        $hitPerPage = null;
        // 表示ページ
        $pageOffset = null;
        // 飲食店情報配列
        $restaurantArray = null;
        // 画面出力用データ
        $viewData = null;
        // ページ数
        $pageCount = 1;

        // 配列のkeyによってデータを振り分ける
        foreach ($responseData as $responseKey => $apiData) {
            switch ($responseKey) {
                case 'total_hit_count':
                    $totalHitCount = $apiData;
                    break;
                case 'hit_per_page':
                    $hitPerPage = $apiData;
                    break;
                case 'page_offset':
                    $pageOffset = $apiData;
                    break;
                case 'rest':
                    $restaurantArray = $apiData;
                    $viewData = json_encode($restaurantArray);
                    break;
                default:
                    break;
            }
        }

        // 1回の実行で取得出来る数よりも合計のデータ数が多い場合
        if ($totalHitCount > $hitPerPage) {
            $pageCount = self::getPageCount($totalHitCount, $hitPerPage);
        }
        return view('maintenance.index')->with('viewData', $viewData)->with('pageOffset', $pageOffset)->with('pageCount', $pageCount);
    }

    // レストラン検索APIの実行
    public function getRestaurantData($offsetNum)
    {
        try {
            /**
             * guzzleHttpClientによるAPI実行
             * /RestSearchAPI/:レストラン検索API
             * keyid:アクセスキー
             * address:地名
             * areacode_m:エリアコード
             * category_l:大業態/RSFST21000=お酒
             * hit_per_page:１ページあたりの店舗情報数学
             * offset_page:街灯のページ数
             **/
            $baseUrl = 'https://api.gnavi.co.jp';
            //$path = '/RestSearchAPI/v3/?keyid=' . env('GURUNAVI_ACCESS_KEY') . '&address=渋谷&category_l=RSFST21000';
            //$path = '/RestSearchAPI/v3/?keyid=' . env('GURUNAVI_ACCESS_KEY') . '&areacode_m=AREAM2126&category_l=RSFST21000';
            //$path = '/RestSearchAPI/v3/?keyid=' . env('GURUNAVI_ACCESS_KEY') . '&areacode_m=AREAM2126&category_l=RSFST21000';
            if ($offsetNum == 1) {
                $path = '/RestSearchAPI/v3/?keyid=' . env('GURUNAVI_ACCESS_KEY') . '&areacode_m=AREAM2126&category_l=RSFST21000&hit_per_page=10&offset_page=1';
            } else {
                $path = '/RestSearchAPI/v3/?keyid=' . env('GURUNAVI_ACCESS_KEY') . '&areacode_m=AREAM2126&category_l=RSFST21000&hit_per_page=10&offset_page=' . $offsetNum;
            }

            $client = new \GuzzleHttp\Client([
                'base_uri' => $baseUrl,
            ]);

            $headers = [
                'Origin'                    => 'https://google.com',
                'Accept-Encoding'           => 'gzip, deflate, br',
                'Accept-Language'           => 'ja,en-US;q=0.8,en;q=0.6',
                'Upgrade-Insecure-Requests' => '1',
                'Content-Type'              => 'application/json; charset=utf-8',
            ];

            $response = $client->request('GET', $path, [
                'allow_redirects' => false,
                'http_errors'     => false,
                'headers'         => $headers,
            ]);

            $responseBody = (string)$response->getBody();

            $responseData = json_decode($responseBody, true);

            // クライアントエラーチェック
            $isClientError = array_key_exists('error', $responseData);
            if ($isClientError) {
                $responseData = "Client error";
            }

            return $responseData;
        } catch (Exception $e) {
            return redirect()->to('errors/500');
        }
    }

    // 店舗情報のページ数の取得
    public function countCheck($num)
    {
        // 数値チェック
        $isDigit = ctype_digit($num);
        // 桁数チェック
        $isRightLength = mb_strlen($num);

        if (($isDigit == false) || ($isRightLength > 2)) {
            return view('errors.404');
        }
    }

    // 店舗情報のページ数の取得
    public function getPageCount($totalCount, $hitCount)
    {
        $execCount = 0;
        $maxDataCount = null;
        // 全てのデータを取得出来るまでのAPIの実行回数を求める
        do {
            $execCount++;
            $maxDataCount = $hitCount * $execCount;
        } while ($totalCount > $maxDataCount);

        return $execCount;
    }

    // DBに登録されている店舗情報の検索
    public function getShopInfoQueryData()
    {
        try {
            // 最新の情報IDを取得する
            $queryData = DB::table('shopinfo')->select('info_id')->latest()->first();

            // 情報IDが取得出来た場合最新の店舗情報を取得
            if (isset($queryData)) {
                $shopInfo = \App\Model\ShopInfo::selectRaw('
                        info_id,
                        shop_id,
                        name,
                        category,
                        areacode_s,
                        areaname_s,
                        category_code_l,
                        category_name_l,
                        category_code_s,
                        category_name_s
                    ')
                    ->whereRaw('info_id = ?', $queryData)
                    ->get();

                self::divideData($shopInfo);
                $areaData = self::getAreaData();
                $categoryData = self::getCategoryData();
            } else {
                return redirect()->to('errors/404');
            }
        } catch (Exception $e) {
            return redirect()->to('errors/500');
        }
    }

    // クエリデータをエリアとカテゴリーに分ける
    public function divideData($registedData)
    {
        $tmpShopId = null;
        $areaTmp = null;
        $categoryTmp = null;
        $returnData = null;

        // 配列のkeyによってデータを振り分ける
        foreach ($registedData as $shopRow) {
            foreach ($shopRow as $rowKey => $rowData) {
                switch ($rowKey) {
                    case 'shop_id':
                        $tmpShopId = $rowData;
                        break;
                    case 'areaname_s':
                        $areaTmp[$tmpShopId] = $rowData;
                        break;
                    case 'category_name_s':
                        $categoryTmp[$tmpShopId] = explode(',', $rowData);
                        break;
                    default:
                        break;
                }
            }
        }
        $returnData['area'] = $areaTmp;
        $returnData['category'] = $categoryTmp;

        return $returnData;
    }

    // エリア情報の取得
    public function getAreaData()
    {
        try {
            // 最新のエリア情報IDを取得する
            $latestAreaInfoId = DB::table('area')->select('area_info_id')->latest()->first();

            // エリアIDが取得出来た場合最新のエリア情報を取得する
            if (isset($latestAreaInfoId)) {
                $areaInfo = \App\Model\Area::where('area_info_id', $latestAreaInfoId->area_info_id)
                    ->orderBy('areacode_s', 'asc')
                    ->get();

                $areaData = null;
                // エリア情報の整形
                foreach ($areaInfo as $areaRow) {
                    $tmpCode = $areaRow->areacode_s;
                    $areaData[$tmpCode] = $areaRow->areaname_s;
                }

                return $areaData;
            } else {
                return redirect()->to('errors/404');
            }
        } catch (Exception $e) {
            return redirect()->to('errors/500');
        }
    }

    //カテゴリー情報の取得
    public function getCategoryData()
    {
        try {
            // 最新のエリア情報IDを取得する
            $latestCategoryInfoId = DB::table('category')->select('category_info_id')->latest()->first();

            // エリアIDが取得出来た場合最新のエリア情報を取得する
            if (isset($latestCategoryInfoId)) {
                $categoryInfo = \App\Model\Category::where('category_info_id', $latestCategoryInfoId->category_info_id)
                    ->orderBy('category_code_s', 'asc')
                    ->get();

                $categoryData = null;
                // カテゴリー情報の整形
                foreach ($categoryInfo as $categoryRow) {
                    $tmpCode = $categoryRow->category_code_s;
                    $categoryData[$tmpCode] = $categoryRow->category_name_s;
                }

                dd($categoryData);
                return $categoryData;
            } else {
                return redirect()->to('errors/404');
            }
        } catch (Exception $e) {
            return redirect()->to('errors/500');
        }
    }

    /** 以下は全てAPIのテスト処理関連 **/
    // API実行テスト処理
    public function apitest()
    {
        // DBクエリテスト
        self::getShopInfoQueryData();

        // APIの実行
        //$responseData = self::execApi();
        $responseData = self::getApiData(0);

        // API実行エラーの場合
        if ($responseData == "Client error") {
            return view('errors.404');
        }

        /* 整形用のデータを作成 */
        // 該当件数
        $totalHitCount = null;
        // 表示件数
        $hitPerPage = null;
        // 表示ページ
        $pageOffset = null;
        // 飲食店情報配列
        $restaurantArray = null;
        $viewData = null;

        // 配列のkeyによってデータを振り分ける
        foreach ($responseData as $responseKey => $apiData) {
            switch ($responseKey) {
                case 'total_hit_count':
                    $totalHitCount = $apiData;
                    break;
                case 'hit_per_page':
                    $hitPerPage = $apiData;
                    break;
                case 'page_offset':
                    $pageOffset = $apiData;
                    break;
                case 'rest':
                    $restaurantArray = $apiData;
                    $viewData = json_encode($restaurantArray);
                    break;
                default:
                    break;
            }
        }
        //dd($restaurantArray);

        // 1回の実行で取得出来る数よりも合計のデータ数が多い場合
        if ($totalHitCount > $hitPerPage) {
            $restaurantArray = self::getModData($totalHitCount, $hitPerPage, $restaurantArray);
            //dd($restaurantArray);
        }

        // 店舗情報をDBへ登録
        self::registShopInfo($restaurantArray);

        //$path = '/RestSearchAPI/v3/?keyid=' . env('GURUNAVI_ACCESS_KEY') . '&areacode_m=AREAM2126&category_l=RSFST21000' . '&';
        //dd($totalHitCount);

        // チェック用
        //dd($restaurantArray);
        //dd($viewData);

        return view('maintenance.apitest')->with('viewData', $viewData);
    }

    // レストラン検索等のAPIの実行
    public function getApiData($offsetNum)
    {
        $baseUrl = 'https://api.gnavi.co.jp';
        /**
         * 飲食店検索
         * guzzleHttpClientによるAPI実行
         * /RestSearchAPI/:レストラン検索API
         * keyid:アクセスキー
         * address:地名
         * areacode_m:エリアコード
         * category_l:大業態/RSFST21000=お酒
         * category_s:小業態
         **/
        if ($offsetNum == 0) {
            $path = '/RestSearchAPI/v3/?keyid=' . env('GURUNAVI_ACCESS_KEY') . '&areacode_m=AREAM2126&category_l=RSFST21000&hit_per_page=100';
        } else {
            $path = '/RestSearchAPI/v3/?keyid=' . env('GURUNAVI_ACCESS_KEY') . '&areacode_m=AREAM2126&category_l=RSFST21000&hit_per_page=100&offset_page='. $offsetNum;
        }

        //dd($path);

        $client = new \GuzzleHttp\Client([
            'base_uri' => $baseUrl,
        ]);

        $headers = [
            'Origin'                    => 'https://google.com',
            'Accept-Encoding'           => 'gzip, deflate, br',
            'Accept-Language'           => 'ja,en-US;q=0.8,en;q=0.6',
            'Upgrade-Insecure-Requests' => '1',
            'Content-Type'              => 'application/json; charset=utf-8',
        ];

        $response = $client->request('GET', $path, [
            'allow_redirects' => false,
            'http_errors'     => false,
            'headers'         => $headers,
        ]);
        $responseBody = (string)$response->getBody();

        $responseData = json_decode($responseBody, true);

        // クライアントエラーチェック
        $isClientError = array_key_exists('error', $responseData);
        if ($isClientError) {
            $responseData = "Client error";
        }

        //dd($responseBody);
        return $responseData;
    }

    // APIの再実行&残りのデータの取得
    public function getModData($totalCount, $hitCount, $restaturantData)
    {
        $execCount = 0;
        $maxDataCount = null;
        // 全てのデータを取得出来るまでのAPIの実行回数を求める
        do {
            $execCount++;
            $maxDataCount = $hitCount * $execCount;
        } while ($totalCount > $maxDataCount);

        // 条件に合う全てのデータを取得するまでAPIを実行する
        for ($i = 2; $i <= $execCount; $i++) {
            //$retryGetData = self::getApiData($i);
            $tmpGetData = self::getApiData($i);
            // 再実行して取得したデータを元の配列に追加する
            foreach ($tmpGetData as $tmpKey => $tmpApiData) {
                if ($tmpKey == 'rest') {
                    foreach ($tmpApiData as $restData) {
                        $restaturantData[] = $restData;
                    }
                }
            }
        }
        return $restaturantData;
    }

    // 各店舗情報をDBに登録
    public function registShopInfo($getData)
    {
        try {
            // 現在のidの最大値を取得
            $newInfoId = self::updateInfoId();

            // 1店舗ごとにテーブルに登録する
            foreach ($getData as $restInfo) {
                // shopInfoオブジェクトを作成
                $shopInfo = new \App\Model\ShopInfo;

                // 値の登録
                $shopInfo->info_id = $newInfoId;
                $shopInfo->shop_id = $restInfo['id'];
                $shopInfo->shop_update_date = $restInfo['update_date'];
                $shopInfo->name = $restInfo['name'];
                $shopInfo->latitude = $restInfo['latitude'];
                $shopInfo->longitude = $restInfo['longitude'];
                $shopInfo->category = $restInfo['category'];
                $shopInfo->url = $restInfo['url'];
                $shopInfo->url_mobile = $restInfo['url_mobile'];
                $shopInfo->coupon_url_pc = $restInfo['coupon_url']['pc'];
                $shopInfo->coupon_url_mobile = $restInfo['coupon_url']['mobile'];
                $shopInfo->shop_image1 = $restInfo['image_url']['shop_image1'];
                $shopInfo->shop_image2 = $restInfo['image_url']['shop_image2'];
                $shopInfo->qrcode = $restInfo['image_url']['qrcode'];
                $shopInfo->address = $restInfo['address'];
                $shopInfo->tel = $restInfo['tel'];
                $shopInfo->fax = $restInfo['fax'];
                $shopInfo->opentime = $restInfo['opentime'];
                $shopInfo->holiday = $restInfo['holiday'];
                $shopInfo->access = $restInfo['access']['line'] . $restInfo['access']['line'] . $restInfo['access']['station'] . $restInfo['access']['station_exit'] . $restInfo['access']['walk'];
                $shopInfo->parking_lots = $restInfo['parking_lots'];
                $shopInfo->pr_short = $restInfo['pr']['pr_short'];
                $shopInfo->pr_long = $restInfo['pr']['pr_long'];
                $shopInfo->areacode_s = $restInfo['code']['areacode_s'];
                $shopInfo->areaname_s = $restInfo['code']['areaname_s'];
                $shopInfo->category_code_l = implode(",", $restInfo['code']['category_code_l']);
                $shopInfo->category_name_l = implode(",", $restInfo['code']['category_name_l']);
                $shopInfo->category_code_s = implode(",", $restInfo['code']['category_code_s']);
                $shopInfo->category_name_s = implode(",", $restInfo['code']['category_name_s']);
                $shopInfo->budget = $restInfo['budget'];
                $shopInfo->party = $restInfo['party'];
                $shopInfo->lunch = $restInfo['lunch'];
                $shopInfo->credit_card = $restInfo['credit_card'];
                $shopInfo->e_money = $restInfo['e_money'];

                // 保存(DBに登録完了)
                $shopInfo->save();
            }
        } catch (Exception $e) {
            $e->getMessage();
            return redirect()->to('errors/500');
        }
    }


    // 情報IDの更新と取得処理
    public function updateInfoId()
    {
        //$newInfoId = DB::table('shopinfo')->select('info_id')->get();
        // 情報IDの最新値を取得して1つ更新した値を返す。
        $queryData = DB::table('shopinfo')->select('info_id')->latest()->first();
        $infoId = null;

        if (isset($queryData)) {
            $tmpId = $queryData->info_id;
            $infoId = ++$tmpId;
        } else {
            $infoId = 1;
        }
        return $infoId;
    }

    // エリア情報をDBに登録
    public function registArea()
    {
        try {
            // categoryオブジェクトを作成
            $area = new \App\Model\Area;

            // 値の登録
            $area->area_info_id = 1;
            $area->areacode_m = "AREAM2126";
            $area->areaname_m = "渋谷";
            $area->areacode_s = "AREAS2128";
            $area->areaname_s = "渋谷南口";

            // 保存(DBに登録完了)
            $area->save();
        } catch (Exception $e) {
            $e->getMessage();
            return redirect()->to('errors/500');
        }
    }

    // カテゴリー情報をDBに登録
    public function registCategory()
    {
        try {
            // categoryオブジェクトを作成
            $category = new \App\Model\Category;

            // 値の登録
            $category->category_info_id = 1;
            $category->category_l_code = "RSFST21000";
            $category->category_l_name = "お酒";
            $category->category_code_s = "RSFST21013";
            $category->category_name_s = "お酒 その他";

            // 保存(DBに登録完了)
            $category->save();
        } catch (Exception $e) {
            $e->getMessage();
            return redirect()->to('errors/500');
        }
    }

    // レストラン検索等のAPIの実行
    public function execApi()
    {
        $baseUrl = 'https://api.gnavi.co.jp';
        /**
         * 飲食店検索
         * guzzleHttpClientによるAPI実行
         * /RestSearchAPI/:レストラン検索API
         * keyid:アクセスキー
         * address:地名
         * areacode_m:エリアコード
         * category_l:大業態/RSFST21000=お酒
         * category_s:小業態
         **/
        //$path = '/RestSearchAPI/v3/?keyid=' . env('GURUNAVI_ACCESS_KEY') . '&address=渋谷&category_l=RSFST21000';
        //$path = '/RestSearchAPI/v3/?keyid=' . env('GURUNAVI_ACCESS_KEY') . '&areacode_m=AREAM2126&category_s=RSFST21001,RSFST21002,RSFST21004';
        //$path = '/RestSearchAPI/v3/?keyid=' . env('GURUNAVI_ACCESS_KEY') . '&areacode_m=AREAM2126&category_l=RSFST21000,RSFST09000';
        $path = '/RestSearchAPI/v3/?keyid=' . env('GURUNAVI_ACCESS_KEY') . '&areacode_m=AREAM2126&category_l=RSFST21000';

        /**
         * 口コミ検索
         * keyid:アクセスキー
         * area:地名
         * hit_per_page:API1回あたりの検索数(最大50)
         * vote_date:現在日時型何日前までの範囲を指定
         * photo_genre_id:写真ジャンルで絞込み/1:料理・ドリンク,2:店内・外観,3:人物・その他
         * comment:コメント
         * menu_name:メニュー名
         **/
        //$path = '/PhotoSearchAPI/v3/?keyid=' . env('GURUNAVI_ACCESS_KEY') . '&area=渋谷&hit_per_page=50&vote_date=720&photo_genre_id=1';

        /**
         * エリア取得
         * エリアLマスタ:GAreaLargeSearchAPI
         * エリアMマスタ:GAreaMiddleSearchAPI
         * エリアSマスタ:GAreaSmallSearchAPI
         * keyid:アクセスキー
         **/
        //$path = '/master/GAreaMiddleSearchAPI/v3/?keyid=' . env('GURUNAVI_ACCESS_KEY');

        /**
         * 業態マスタ取得
         * 大業態マスタ:CategoryLargeSearchAPI
         * 小業態マスタ:CategorySmallSearchAPI
         * keyid:アクセスキー
         **/
        //$path = '/master/CategorySmallSearchAPI/v3/?keyid=' . env('GURUNAVI_ACCESS_KEY');

        //dd($path);

        $client = new \GuzzleHttp\Client([
            'base_uri' => $baseUrl,
        ]);

        $headers = [
            'Origin'                    => 'https://google.com',
            'Accept-Encoding'           => 'gzip, deflate, br',
            'Accept-Language'           => 'ja,en-US;q=0.8,en;q=0.6',
            'Upgrade-Insecure-Requests' => '1',
            'Content-Type'              => 'application/json; charset=utf-8',
        ];

        $response = $client->request('GET', $path, [
            'allow_redirects' => false,
            'headers'         => $headers,
        ]);
        $responseBody = (string)$response->getBody();

        $responseData = json_decode($responseBody, true);

        dd($responseBody);
        return $responseData;
    }
}
