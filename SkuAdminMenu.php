<?php

    //use Foru\Model\PostMeta;

     if ( !defined('ABSPATH') ) {
        exit;
    }

    class SkuAdminMenu
    {
        protected $pageSlug;
        protected $messages;
        protected $postMeta;

        public function __construct()
        {
            $this->pageSlug = 'manage_my_sku';
            $this->messages = [];
            //$this->postMeta = new PostMeta();
            add_action('admin_menu',array($this,'addAdminMenu'));

        }

        public function addAdminMenu()
        {
            $menu_hook =  add_menu_page (
                'wp_sku',
                'Manage Sku',
                'manage_options',
                $this->pageSlug,
                array($this, 'showSkuHtml' )
            );

            add_action('load-'.$menu_hook, array($this, 'handlePost'));
            add_action('admin_notices' , array($this, 'showPageNotice'));
        }

        public function handlePost()
        {

            if( isset($_POST['replacebutton'] ) && !empty($_POST['cate_name']) && !empty( $_POST['blog_id'] ))
            {
                check_admin_referer('wp_sku',$this->pageSlug);

                $this->dealData($_POST['blog_id'],$_POST['cate_name']);
                $this->messages[] = ['status' => 'success', 'message' => 'successfully deal'];
            }

            if ( isset($_POST['clearbutton'] ))
            {
                check_admin_referer('wp_sku',$this->pageSlug);

                $this->clearData();
                $this->messages[] = ['status' => 'success', 'message' => 'successfully clear'];
            }

            if( isset($_POST['addbutton']))
            {

                check_admin_referer('wp_sku',$this->pageSlug);
                $this->addData();
                //$this->messages[] = ['status' => 'success','message' => 'successfully add'];
            }

        }

        public function modifyProductAttribute($data)
        {
            return [
                    'size' =>
                       ['name' => $data[0]['size']['name'],
                         'value' => 'L(ADULT SIZE AGE 13+)|M(YOUTH SIZE AGE 8 TO 12)|S(CHILD SIZE AGE 4 TO 7)',
                         'position' => $data[0]['size']['position'],
                         'is_visible' => $data[0]['size']['is_visible'],
                         'is_variation' => $data[0]['size']['is_variation'],
                         'is_taxonomy' => $data[0]['size']['is_taxonomy'],
                        ],
                 ];
        }

        public function modifyAttributeSize()
        {
            return [
                     'L(ADULT SIZE AGE 13+)','M(YOUTH SIZE AGE 8 TO 12)','S(CHILD SIZE AGE 4 TO 7)'
            ];
        }

        public function getData()
        {
            $arg = [
                'post_type' => 'product',
                 'fields' => 'ids'
            ];

            $products = get_posts($arg);

            return $products;
        }

        public function dealData($blog_id,$cate_name)
        {
            //$productIds = $this->getData();//get product ids
            //get productIds

            global $wpdb;
            if ( $blog_id == 1 ) {
                $current_relationshios_table = 'wp_term_relationships';
            } else {
                $current_relationshios_table = 'wp_'.$blog_id.'_term_relationships';
            }

            $termIds = get_terms(array('name' => $cate_name,'fields' => 'ids'));
            $sql = "select object_id from `$current_relationshios_table` where term_taxonomy_id = ".$termIds[0]; //need 优化
            $productIds = $wpdb->get_results($sql,ARRAY_A);

            foreach($productIds as $postId)
            {

                $old_product_attributes = get_post_meta($postId['object_id'],'_product_attributes');//get old _product_attributes

                //这里是判断是否由这个属性，而不是判断是否由这个属性的值，所以保证了有这个属性必须由值
                if ( !empty( $old_product_attributes ) )
                {
                    //modify _product_attributes
                    $new_product_attributes = $this->modifyProductAttribute($old_product_attributes);

                    //replace _product_attributes
                    update_post_meta($postId['object_id'],'_product_attributes',$new_product_attributes);

                    //get postid of variation
                    $variationIds = get_posts(array( 'post_type'=>'product_variation', 'post_parent' => $postId['object_id'], 'fields' => 'ids'));

                    //get new  _attribute_size
                    $new_attribute_size = $this->modifyAttributeSize();

                    //replace _attribute_size
                    foreach($variationIds as $key=>$variationid)
                    {
                        update_post_meta($variationid,'attribute_size',$new_attribute_size[$key]);
                    }

                }

            }
        }

        public function requestData($sku)//from url
        {
            $url = sku_config_helper('pull_product_url').$sku;

             try{
                $response = \Requests::get($url,  array(
                    'Accept' => 'application/json',
                ),
                array(
                    'verify' => false,
                ));

                if($response->status_code == 200)
                {
                    $result =  json_decode($response->body, true);
                    return $result;
                }
            }catch(Exception $e){}
        }


        public function saveData()
        {
            //  $this->postMeta
            // ->create([
            //     'post_id' => $post_id,
            //     'meta_key' => '_product_attributes',
            //     'meta_value' => serialize($product_attributes),
            // ]);
        }

        public function clearData()
        {

            $productIds = $this->getData();

            foreach($productIds as $postId)
            {

                 if ( !empty( get_post_meta($postId,'_product_attributes') ) )
                 {

                    update_post_meta($postId,'_product_attributes','');

                    $variationIds = get_posts(array( 'post_type'=>'product_variation', 'post_parent' => $postId, 'fields' => 'ids'));

                    foreach($variationIds as $variationid)
                    {
                        update_post_meta($variationid,'attribute_size','');
                    }
                }

           }

        }

        public function addData()
        {
            check_admin_referer('wp_sku',$this->pageSlug);

                $size1 =[
                    'size' =>
                       ['name'=>'size','value'=>'S|M|L','position'=>0,'is_visible'=>0,'is_variation'=>1,'is_taxonomy'=>0],
                ];


                $size2 = [
                    'S','M','L'
                ];

                $productIds = $this->getData();

                 foreach($productIds as $postId)
                {

                    if ( !empty( get_post_meta($postId,'_product_attributes') ) ) {

                            update_post_meta($postId,'_product_attributes',$size1);

                            $variationIds = get_posts(array( 'post_type'=>'product_variation', 'post_parent' => $postId, 'fields' => 'ids'));

                            foreach($variationIds as $key => $variationid)
                            {
                                update_post_meta($variationid,'attribute_size',$size2[$key]);
                            }
                              $this->messages[] = ['status' => 'success','message' => $postId];

                    }
                    else
                    {
                        $this->messages[] = ['status' => 'success','message' => 00];
                    }

             }

        }

        public function showSkuHtml()
        {
            include_once(__DIR__.'/view/ShowSkuHtml.php');
        }

        //显示notic信息
        public function showPageNotice()
        {
            foreach ($this->messages  as $message) {
                    if ($message['status'] == 'success') {
                        echo sprintf("<div class='notice notice-success'><p><strong>%s</strong></p></div>",$message['message']);
                    } else {
                        echo sprintf("<div class='notice notice-warning'><p><strong>%s</strong></p></div>",$message['message']);
                    }
            }
        }

    //  public function deleteSkuProductAttribute($post_id)
    // {

    //     /*handle product already exists end*/
    //     //2017.10.17
    //    //  foreach($product['variations'] as $key => $variation){
    //    //      $sizestr = explode(";",$variation[4]);
    //    //      $size = explode(":",$sizestr[0]);
    //    //      $newSize = $size[0].':'.'US(EU'.$size[1].')'.';'.$sizestr[1];
    //    //      $product['variations'][$key][4] =  $newSize;
    //    // }

    //     update_post_meta($post_id, forudesigns_config('template_key'), "");

    //     //插入分类（数组）
    //     if(!empty($category))
    //     {
    //         if(!is_array($category))
    //         {
    //             $category = [$category];
    //         }
    //         foreach ($category as $category_row) {
    //             $this->term_relationships
    //             ->create([
    //                 'object_id' => $post_id,
    //                 'term_taxonomy_id' => $category_row,
    //             ]);
    //         }
    //     }

    //     // $this->postMeta
    //     //     ->create([
    //     //         'post_id' => $post_id,
    //     //         'meta_key' => '_visibility',
    //     //         'meta_value' => 'visible',
    //     //     ]);

    //      update_post_meta($post_id, '_visibility', "");

    //     // $this->postMeta
    //     //     ->create([
    //     //         'post_id' => $post_id,
    //     //         'meta_key' => '_product_source',
    //     //         'meta_value' => 'foru_drop_shipping',
    //     //     ]);
    //     update_post_meta($post_id, '_product_source', "");

    //     $this->insert_product_images($post_id, $product['images'], $schedule, $average);

    //     // wp_set_object_terms($post_id, explode(',',$product_data['category']), 'product_cat');
    //     if (count($product['variations']) > 1) {
    //         wp_set_object_terms($post_id, 'variable', 'product_type');
    //         $attributes = $variations = [];
    //         foreach ($product['variations'] as $key => $value) {
    //             if (empty($value)) {
    //                 continue;
    //             }
    //             $variations[] = $value;
    //         }
    //         foreach ($product['variations'] as $key => $value) {
    //             if (isset($value[4]) && !empty($value[4])) {
    //                 $attributes[] = $value[4];
    //             }
    //         }
    //         $this->insertProductAttributes($post_id, $attributes);
    //         $this->insertProductVariations($post_id, $variations);
    //     } elseif (count($product['variations']) == 1) {
    //         $data = array(
    //             [
    //                 'post_id' => $post_id,
    //                 'meta_key' => '_sku',
    //                 'meta_value' => empty($sku = 'FORU_'.$product['variations'][0][1]) ? '' : $sku,
    //             ],
    //             [
    //                 'post_id' => $post_id,
    //                 'meta_key' => '_price',
    //                 'meta_value' => empty($price = $product['variations'][0][2]) ? '' : $price,
    //             ],
    //             [
    //                 'post_id' => $post_id,
    //                 'meta_key' => '_regular_price',
    //                 'meta_value' => empty($regular_price = $product['variations'][0][2]) ? '' : $regular_price,
    //             ],
    //             [
    //                 'post_id' => $post_id,
    //                 'meta_key' => '_weight',
    //                 'meta_value' => empty($weight = $product['variations'][0][3]) ? '' : $weight,
    //             ],
    //         );

    //         foreach ($data as $data_row) {
    //             $this->postMeta->create($data_row);
    //         }
    //     }
    //     return $post_id;
    // }

    }

    $skuadminmenu = new SkuAdminMenu;