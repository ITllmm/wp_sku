
    <div class="wrap">
        <h1 class="wp-heading-inline">wp-sku</h1>
        <form action="<?php echo admin_url('admin.php?page='.$this->pageSlug); ?>" method="post">
            <?php wp_nonce_field('wp_sku', $this->pageSlug); ?>
            <p>
                <strong>cate_name: </strong>
                <input type="text" class="large-text" name="cate_name" id="cate_name" >
            </p>
            <button class="button-primary button" name="clearbutton" >clear datas</button>
            <button class="button-primary button" name="addbutton" >add datas</button>
            <button class="button-primary button" name="replacebutton" >replace datas</button>
        </form>
    </div>





