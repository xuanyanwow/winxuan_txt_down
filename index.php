<?php
require_once "./vendor/autoload.php";

use GuzzleHttp\Client;


down("0dba0e86-0515-4661-866d-0643cf30d48d", "1202412301", "苏东坡新传", 13,33720);


function siam_log($content){
    if (!is_string($content)){
        echo "\n";
        var_dump($content);
        echo "\n";
        return ;
    }
    echo "\n{$content} \n";
}


function down($member_authorization, $product_sale_id, $book_name, $chapter = 1,$index = 0){
    siam_log("当前下载, 章节{$chapter}, 字符{$index}");
    $client = new Client([
        'base_uri' => 'https://membermp.winxuan.com/',
        'timeout'  => 10.0,
    ]);
    $json = '{"productSaleId":"'.$product_sale_id.'","chapter":'.$chapter.',"beginIndex":'.$index.',"width":358.8,"height":603.44,"fontSize":18.76,"fontName":"微软雅黑","rowHeight":"10","lineWidth":3,"model":"1","lastFlag":false,"schedule":null}';

    try {
        $content = $client->request("POST", "ebook/content", [
            'headers' => [
                'Content-Type'        => 'application/json;charset=UTF-8',
                'memberAuthorization' => $member_authorization,
            ],
            'json' => json_decode($json,true)
        ]);

        $response = $content->getBody();
        $response = json_decode($response, true);
        if ($response['code']!=200){
            siam_log($response);
            return ;
        }
        if ($response['result'] === null){
            siam_log('下载完成1');
            return ;
        }

        $result = $response['result']['ebookPageContentVOS'];

        $save_content  = '';
        $last_line_ypx = '';
        $last_index    = false;

        foreach ($result as $font){
            // 如果文字的xpx=42了，并且上一个元素y轴不同  那么就是换行缩进了
            if ($font['xpx'] == 42 && $font['ypx'] !== $last_line_ypx){
                $save_content .= "\n    ";
            }
            $last_line_ypx = $font['ypx'];

            // 判断是否为图片
            if (!empty($font['imageUrl'])){
                $save_content .= "\n【图片：{$font['imageUrl']}】\n";
            }else{
                $save_content .= $font['content'];
            }
            if ($font['index'] !== null){
                $last_index = $font['index'];
            }
        }



        $path = __DIR__.'/save/'.$book_name.'.txt';

        file_put_contents($path, $save_content,FILE_APPEND);

        if ($response['result']['endFlag']){
            // 本章结束 进入下一章
            down($member_authorization, $product_sale_id, $book_name, $chapter+1, 0);
            return ;
        }
        if ($last_index !== false){
            down($member_authorization, $product_sale_id, $book_name, $chapter,$last_index+1);
            return ;
        }


    }catch (\Exception $exception){
        echo $exception->getMessage();
    }
}