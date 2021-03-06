<?php

namespace Tests\Unit;

use Tests\Unit\MainPageCommon;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MainPageMock extends MainPageCommon
{
    /**
     * @var MainPageCommon
     */
    protected $commonObj;

    /**
     * setUpは各テストメソッドが実行される前に実行する
     * 親クラスのsetUpを必ず実行する
     */
    protected function setUp(): void
    {
        parent::setUp();

        // テストするオブジェクトを生成する
        $this->commonObj = new MainPageCommon();

        $testData = array(
            'total_hit_count' => 181,
            'hit_per_page' => 10,
            'page_offset' => 1,
            'rest' => array(
                0 => array(
                    'id' => 'test1120',
                    'update_date' => '2016-6-2',
                    'name' => 'テスト渋谷居酒屋',
                    'category' => '渋谷カフェ＆ダイニング',
                    'url' => 'http://localhost',
                    'url_mobile' => 'http://localhost',
                    'coupon_url' => array(
                        'pc' => 'http://localhost',
                    ),
                    'image_url' => array(
                        'shop_image1' => 'http://localhost',
                        'shop_image2' => 'http://localhost',
                    ),
                    'address' => '〒150-0044 東京都渋谷区テスト町1-2-3 テストビル',
                    'tel' => '123-1234-1234',
                    'opentime' => '月～金 ランチ：12:00～15:00(L.O.14:30)、ディナー：18:00～24:00(L.O.23:00、ドリンクL.O.23:30) \n土・日・祝 ディナー：18:00～24:00(L.O.23:00、ドリンクL.O.23:30)',
                    'holiday' => '不定休日あり',
                    'access' => array(
                        'line' => 'JR',
                        'station' => '渋谷駅',
                        'station_exit' => 'ハチ公口',
                        'walk' => '10',
                    ),
                    'pr' => array(
                        'pr_short' => 'テスト渋谷居酒屋のショート版PR',
                        'pr_long' => 'テスト渋谷居酒屋のロング版PR',
                    ),
                    'code' => array(
                        'areacode' => 'AREA110',
                        'areaname' => '関東',
                        'prefcode' => 'PREF13',
                        'prefname' => '東京都',
                        'areacode_s' => 'AREAS2126',
                        'areaname_s' => '道玄坂・神泉',
                        'category_code_l' => array(
                            0 => 'RSFST10000',
                            1 => 'RSFST21000',
                        ),
                        'category_name_l' => array(
                            0 => 'ダイニングバー・バー・ビアホール',
                            1 => 'お酒',
                        ),
                        'category_code_s' => array(
                            0 => 'RSFST10005',
                            1 => 'RSFST21008',
                        ),
                        'category_name_s' => array(
                            0 => 'バー',
                            1 => 'カクテル',
                        ),
                    ),
                    'budget' => 3000,
                    'party' => 3000,
                    'lunch' => 950,
                    'credit_card' => 'VISA,MasterCard',
                ),
            ),
        );
        $this->commonObj->data = $testData;
    }

    /**
     * データ分類のテスト
     */
    public function classificationData($target, $searchKey, $commonObj)
    {
        $test = isset($commonObj->data);
        if (!$test) {
            return false;
        }
        $checkData = $commonObj->data;

        $searchData = null;

        // 配列のkeyによってデータを振り分ける
        foreach ($checkData as $responseKey => $apiData) {
            switch ($responseKey) {
                case $searchKey:
                    $searchData = $apiData;
                    break;
                default:
                    break;
            }
        }

        $existKey = array_key_exists($searchKey, $checkData);
        if ($existKey === false) {
            print $commonObj->getDate() . "$searchKey is no exist." . "\n";
            return $existKey;
        }

        if ($target == 'key') {
            if (is_array($searchData)) {
                print $commonObj->getDate() . "$searchKey is array data" . "\n";
            } else {
                print $commonObj->getDate() . "$searchKey is $searchData" . "\n";
            }
            return $existKey;
        } elseif ($target == 'data') {
            if (is_array($searchData)) {
                print $commonObj->getDate() . "$searchKey is array data" . "\n";
                return false;
            } else {
                print $commonObj->getDate() . "$searchKey is $searchData" . "\n";
            }
            return $searchData;
        } else {
            return false;
        }
    }

    /**
     * 店舗情報のページ数の取得
     */
    public function getPageCount($totalCount, $hitCount, $commonObj)
    {
        $countOver = $commonObj->checkTotalHitCount($totalCount, $hitCount);

        if ($countOver) {
            $execCount = 0;
            $maxDataCount = null;
            // 全てのデータを取得出来るまでのAPIの実行回数を求める
            do {
                $execCount++;
                $maxDataCount = $hitCount * $execCount;
            } while ($totalCount > $maxDataCount);

            print $commonObj->getDate() . "execCount is $execCount" . "\n";
            return $execCount;
        } else {
            return false;
        }
    }

    /**
     * DBに登録されている店舗情報IDの検索
     */
    public function getShopInfoQueryCount($commonObj)
    {
        $latestId = $commonObj->getShopInfoCount();
        if (!$latestId) {
            print $commonObj->getDate() . "ShopInfoId None." . "\n";
            return false;
        } else {
            print $commonObj->getDate() . "Latest shopInfoId $latestId" . "\n";
            return true;
        }
    }

    /**
     * DBに登録されている店舗情報の検索
     */
    public function getShopInfoQueryData($targetId, $commonObj)
    {
        $shopInfo = $commonObj->getShopInfoData($targetId);
        if (!$shopInfo) {
            print $commonObj->getDate() . "ShopInfoId None." . "\n";
            return false;
        } else {
            $shopData = null;
            foreach ($shopInfo as $shopRow) {
                $tmpId = $shopRow->shop_id;
                $tmpShopName = $shopRow->name;
                $tmpShopURL = $shopRow->url;
                $tmpShopImage1 = $shopRow->shop_image1;
                $tmpAreaCode = $shopRow->areacode_s;
                $thisCategoryCode = explode(',', $shopRow->category_code_s);
                $thisCategoryName = explode(',', $shopRow->category_name_s);
                $tmpCategoryName = $thisCategoryName;

                $shopData[$tmpId]['name'] = $tmpShopName;
                $shopData[$tmpId]['url'] = $tmpShopURL;
                $shopData[$tmpId]['shop_image1'] = $tmpShopImage1;
                $shopData[$tmpId]['area'] = $tmpAreaCode;
                $shopData[$tmpId]['category_code'] = $thisCategoryCode;
                $shopData[$tmpId]['category_name'] = $tmpCategoryName;
            }
            print $commonObj->getDate() . "Get Latest shopInfo" . "\n";
            return $shopData;
        }
    }

    /**
     * DBに登録されている店舗情報の検索
     */
    public function getAreaData($commonObj)
    {
        $areaInfo = $commonObj->getAreaQuery();
        if (!$areaInfo) {
            print $commonObj->getDate() . "AreaData None." . "\n";
            return false;
        } else {
            $areaData = null;
            foreach ($areaInfo as $areaRow) {
                $tmpCode = $areaRow->areacode_s;
                $areaData[$tmpCode] = $areaRow->areaname_s;
            }
            print $commonObj->getDate() . "Get Latest AreaData" . "\n";
            return $areaData;
        }
    }

    /**
     * DBに登録されているカテゴリー情報の検索
     */
    public function getCategoryData($commonObj)
    {
        $categoryInfo = $commonObj->getCategoryQuery();
        if (!$categoryInfo) {
            print $commonObj->getDate() . "AreaData None." . "\n";
            return false;
        } else {
            $categoryData = null;
            // カテゴリー情報の整形
            foreach ($categoryInfo as $categoryRow) {
                $tmpCode = $categoryRow->category_code_s;
                $categoryData[$tmpCode] = $categoryRow->category_name_s;
            }
            print $commonObj->getDate() . "Get Latest CategoryData" . "\n";
            return $categoryData;
        }
    }
}
