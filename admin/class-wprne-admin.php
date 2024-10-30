<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @since      1.0.0
 *
 * @package    WPRNE
 * @subpackage WPRNE/admin
 */
// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}
/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    WPRNE
 * @subpackage WPRNE/admin
 */
class WPRNE_Admin
{
    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $WPRNE    The ID of this plugin.
     */
    private  $WPRNE;
    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private  $version;
    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $WPRNE       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct($WPRNE, $version)
    {
        $this->WPRNE = $WPRNE;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles()
    {
        $page = (isset($_GET['page']) ? sanitize_text_field($_GET['page']) : "");
        if (empty($page) || $page !== 'app-editor') {
            return;
        }
        wp_enqueue_style(
            'editor-style',
            WPRNE_PLUGIN_URL . '/assets/css/editor-style.css',
            array(),
            $this->version,
            'all'
        );
        wp_enqueue_style(
            'editor-main-style',
            WPRNE_PLUGIN_URL . '/assets/css/editor-main-style.css',
            array(),
            $this->version,
            'all'
        );
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts()
    {

        $page = (isset($_GET['page']) ? sanitize_text_field($_GET['page']) : "");
        if (empty($page) || $page !== 'app-editor') {
            return;
        }
        wp_enqueue_script(
            'editor-script',
            WPRNE_PLUGIN_URL . '/assets/js/editor-script.js',
            array(),
            $this->version,
            true
        );
        wp_enqueue_script(
            'editor-main-script',
            WPRNE_PLUGIN_URL . '/assets/js/editor-main-script.js',
            array(),
            $this->version,
            true
        );
        wp_enqueue_script(
            'editor-runtime-script',
            WPRNE_PLUGIN_URL . '/assets/js/editor-runtime-script.js',
            array(),
            $this->version,
            true
        );
        wp_enqueue_script(
            'editor-display-script',
            WPRNE_PLUGIN_URL . '/assets/js/editor-display.js',
            array("jquery"),
            $this->version,
            true
        );
		wp_enqueue_script(
            'payhip-script',
            'https://payhip.com/payhip.js',
            $this->version,
            true
        );
        $settings = get_option('wprne_settings', array());
        $woock = (!empty($settings['woock']) ? $settings['woock'] : '');
        $woocs = (!empty($settings['woocs']) ? $settings['woocs'] : '');
        $license = get_option("wprne_license_data");
        $localize_data = array(
            'baseUrl'    => home_url('/'),
            'pluginUrl'  => WPRNE_PLUGIN_URL,
            'translate'  => self::get_translate(),
            'ck'         => $woock,
            'cs'         => $woocs,
            'nonce'      => wp_create_nonce('wp_rest'),
            'license'    => $license,
            'upgradeUrl' => "https://payhip.com/b/w2OLv",
        );    

        wp_localize_script('editor-script', 'wprneLocalize', $localize_data);
    }

    /**
     * Admin menu
     *
     * @since    1.0.0
     */
    public function admin_menu()
    {
        add_menu_page(
            'app-editor',
            __('Mobile App Editor', 'wprne'),
            'edit_others_posts',
            'app-editor',
            array($this, 'wprne_menu_page'),
            'dashicons-smartphone',
            50
        );
    }

    /**
     * Publish post
     *
     * @since    1.0.0
     */
    public function publish_post($ID, $post)
    {
        $post_id = $post->ID;
        $push_notif_sent = get_post_meta($post_id, 'wprne_push_notif_sent', true);
		$is_send_push = get_option("wprne_send_post_pushnotif");
        if ($is_send_push && empty($push_notif_sent) && !wp_is_post_revision($post_id)) {
            $tokens = get_option('wprne_push_notif_token', array());
            $title = get_the_title($post_id);
			$urlparts = parse_url(get_site_url());
			$domain = $urlparts["host"];
			$body = [];
            foreach ($tokens as $token => $value) {				
				if($value){
					$body[] = [
						'to'  => $token,
						'sound' => 'default',
						'title' => $domain,
						'body' => $title,
						'priority' => 'high',
					];
				}
            }
			$endpoint = 'https://exp.host/--/api/v2/push/send';

			$body = wp_json_encode($body);

			$options = [
				'body'        => $body,
				'headers'     => [
					'Content-Type' => 'application/json',
				],
				'timeout'     => 60,
				'redirection' => 5,
				'blocking'    => true,
				'httpversion' => '1.0',
				'sslverify'   => false,
				'data_format' => 'body',
			];

			$response = wp_remote_post($endpoint, $options);

            update_post_meta($post_id, 'wprne_push_notif_sent', true);
        }
    }

    /**
     * Admin menu callback
     *
     * @since    1.0.0
     */
    public function wprne_menu_page()
    {
?>
        <style>
            #wpbody-content {
                display: none !important;
            }
        </style>
        <div id="wprne-container" style="position:fixed;">
            <div id="root" style="height:100vh; overflow-y:scroll; min-width:calc(100vw - 160px);"></div>
            <style type="text/css">
                @font-face {
                    font-family: 'MaterialIcons';
                    src: url(<?php
                                echo  esc_url(WPRNE_PLUGIN_URL . '/assets/media/MaterialIcons.ttf');
                                ?>) format('truetype');
                }

                @font-face {
                    font-family: 'FontAwesome';
                    src: url(<?php
                                echo  esc_url(WPRNE_PLUGIN_URL . '/assets/media/FontAwesome.ttf');
                                ?>) format('truetype');
                }
            </style>
        </div>
<?php
    }

    /**
     * Get all translatable word for script localize data.
     *
     * @since    1.0.0
     */
    public static function get_translate()
    {
        return array(
            'pluginTitle'          => __('Mobile App Editor', 'wprne'),
            'apps'                 => __('Apps', 'wprne'),
            'newApp'               => __('New App', 'wprne'),
            'editApp'              => __('Edit app', 'wprne'),
            'deleteApp'            => __('Delete app', 'wprne'),
            'newAppTemplate'       => __('Create new app using this template', 'wprne'),
            'createNewApp'         => __('Create New App', 'wprne'),
            'appName'              => __('App name', 'wprne'),
            'deleteAppConfirm'     => __('Are you sure to delete', 'wprne'),
            'errorNewApp'          => __('Something went wrong trying to create new app"', 'wprne'),
            'title'                => __('Title', 'wprne'),
            'buttonType'           => __('Button Type', 'wprne'),
            'font'                 => __('Font', 'wprne'),
            'icon'                 => __('Icon', 'wprne'),
            'fontSize'             => __('Font Size', 'wprne'),
            'fontColor'            => __('Font Color', 'wprne'),
            'iconSize'             => __('Icon Size', 'wprne'),
            'iconColor'            => __('Icon Color', 'wprne'),
            'onPressAction'        => __('On Press Action', 'wprne'),
            'autoplay'             => __('Autoplay', 'wprne'),
            'autoplayHint'         => __('Autoplay always disabled in builder mode', 'wprne'),
            'dot'                  => __('Dot', 'wprne'),
            'dotHint'              => __('Dot always enabled in builder mode', 'wprne'),
            'enable'               => __('Enable', 'wprne'),
            'delay'                => __('Delay (second)', 'wprne'),
            'activeColor'          => __('Active Color', 'wprne'),
            'productPreview'       => __('Product Preview', 'wprne'),
            'addToCart'            => __('Add to Cart', 'wprne'),
            'direction'            => __('Direction', 'wprne'),
            'justify'              => __('Justify Content', 'wprne'),
            'justifyHint'          => __('Align items along the main axis of their container', 'wprne'),
            'alignItems'           => __('Align Items', 'wprne'),
            'alignItemsHint'       => __('Align items along the cross axis of their container', 'wprne'),
            'scrollable'           => __('Scrollable', 'wprne'),
            'itemDirection'        => __('Item Direction', 'wprne'),
            'showScroll'           => __('Show Scroll', 'wprne'),
            'numColumns'           => __('Number of Column', 'wprne'),
            'postType'             => __('Post Type', 'wprne'),
            'postsCount'           => __('Posts Count', 'wprne'),
            'category'             => __('Category', 'wprne'),
            'order'                => __('Order', 'wprne'),
            'orderby'              => __('Order By', 'wprne'),
            'keyword'              => __('Keyword', 'wprne'),
            'postQuery'            => __('Post Query', 'wprne'),
            'changePostQuery'      => __('Change Post Query', 'wprne'),
            'bestSeller    '       => __('Best Seller', 'wprne'),
            'featured'             => __('Featured', 'wprne'),
            'onSale'               => __('On Sale', 'wprne'),
            'changeProductQuery'   => __('Change Product Query', 'wprne'),
            'source'               => __('Source', 'wprne'),
            'postContent'          => __('Post Content', 'wprne'),
            'resizeMode'           => __('Resize Mode', 'wprne'),
            'carouselSize'         => __('Carousel Size', 'wprne'),
            'carouselSizeHint'     => __('Use fixed number or % (ex: 32, 50%)', 'wprne'),
            'width'                => __('Width', 'wprne'),
            'height'               => __('Height', 'wprne'),
            'postPreview'          => __('Post Preview', 'wprne'),
            'contentType'          => __('Content Type', 'wprne'),
            'staticContent'        => __('Static Content', 'wprne'),
            'htmlContent'          => __('Html Content', 'wprne'),
            'asWebView'            => __('As Web View', 'wprne'),
            'charLength'           => __('Character Length', 'wprne'),
            'fontStyle'            => __('Font Style', 'wprne'),
            'fontWeight'           => __('Font Weight', 'wprne'),
            'textAlign'            => __('Text Align', 'wprne'),
            'textTransform'        => __('Text Transform', 'wprne'),
            'lineHeight'           => __('Line Height', 'wprne'),
            'videoId'              => __('Video ID', 'wprne'),
            'insertComponent'      => __('Insert selected component', 'wprne'),
            'container'            => __('Container', 'wprne'),
            'button'               => __('Button', 'wprne'),
            'text'                 => __('Text', 'wprne'),
            'image'                => __('Image', 'wprne'),
            'carousel'             => __('Carousel', 'wprne'),
            'gridPost'             => __('Grid Post', 'wprne'),
            'allComponents'        => __('All Components', 'wprne'),
            'moveUp"'              => __('move up', 'wprne'),
            'moveDown'             => __('move down', 'wprne'),
            'duplicate'            => __('duplicate', 'wprne'),
            'delete'               => __('delete', 'wprne'),
            'cut'                  => __('cut', 'wprne'),
            'copy'                 => __('copy', 'wprne'),
            'paste'                => __('paste', 'wprne'),
            'saveToTemplate'       => __('save template', 'wprne'),
            'saveTemplate'         => __('Save Template', 'wprne'),
            'movedUpWarning'       => __('This component can\'t be moved up', 'wprne'),
            'movedDownWarning'     => __('This component can\'t be moved down', 'wprne'),
            'deletedWarning'       => __('This component can\'t be deleted', 'wprne'),
            'duplicatedwarning'    => __('This component can\'t be duplicated', 'wprne'),
            'savedWarning'         => __('This component can\'t be saved', 'wprne'),
            'cutWarning'           => __('This component can\'t be cut', 'wprne'),
            'copiedWarning'        => __('This component can\'t be copied', 'wprne'),
            'pastedWarning'        => __('Component can only be inserted to a container', 'wprne'),
            'alertSettingsTitle'   => __('Click on a component in builder mode to edit it\'s properties', 'wprne'),
            'alertSettingsText'    => __('You could also select component from layers panel', 'wprne'),
            'flex'                 => __('Flex', 'wprne'),
            'flexHint'             => __('Flex will define how your items are going to “fill” over the available space along your main axis. Space will be divided according to each element\'s flex property.', 'wprne'),
            'size'                 => __('Size', 'wprne'),
            'sizeHint'             => __('Use auto, fixed number or % (ex: 32, 50%)', 'wprne'),
            'backgroundColor'      => __('Background Color', 'wprne'),
            'colorHint'            => __('Use color name, hex format or rgba format (ex: black, red, #FFF, rgba(0,0,0,1))', 'wprne'),
            'dynamicColor'         => __('Dynamic Background Color', 'wprne'),
            'margin'               => __('Margin', 'wprne'),
            'marginHint'           => __('Use auto, fixed number or % (ex: 16, 10%)', 'wprne'),
            'padding'              => __('Padding', 'wprne'),
            'position'             => __('Position', 'wprne'),
            'borderRadius'         => __('Border Radius', 'wprne'),
            'border'               => __('Border', 'wprne'),
            'buildApk'             => __('Build APK', 'wprne'),
            'build'                => __('Build', 'wprne'),
            'close'                => __('Close', 'wprne'),
            'refresh'              => __('Refresh', 'wprne'),
            'inputImage'           => __("Drag 'n' drop some files here, or click to select files", 'wprne'),
            'githubToken'          => __('Github token', 'wprne'),
            'expoUser'             => __('Expo user name', 'wprne'),
            'expoPassword'         => __('Expo password', 'wprne'),
            'appName'              => __('App name', 'wprne'),
            'appSlug'              => __('App slug', 'wprne'),
            'appPackage'           => __('App package', 'wprne'),
            'appVersion'           => __('App version', 'wprne'),
            'androidVersioCode'    => __('App version number', 'wprne'),
            'appIcon'              => __('App icon (png 1024 x 1024)', 'wprne'),
            'splashImage'          => __('Splash screen image (png)', 'wprne'),
            'repoCreated'          => __('The repository has been created', 'wprne'),
            'buildRun'             => __('Build process is running', 'wprne'),
            'githubActionError'    => __('Github actions not found', 'wprne'),
            'buildError'           => __('There is an error, build process stopped', 'wprne'),
            'checkRepo'            => __('Create the repository on github', 'wprne'),
            'minimize'             => __('Minimize', 'wprne'),
            'fullscreen'           => __('Fullscreen', 'wprne'),
            'undo'                 => __('Undo', 'wprne'),
            'redo'                 => __('Redo', 'wprne'),
            'saveSettings'         => __('Save Settings', 'wprne'),
            'woock'                => __('Woocommerce API Consumer Key', 'wprne'),
            'woocs'                => __('Woocommerce API Consumer Secret', 'wprne'),
            'settings'             => __('Settings', 'wprne'),
            'deleteConfirm'        => __('Are You sure to delete this template?', 'wprne'),
            'insertTemplate'       => __('insert template', 'wprne'),
            'deleteTemplate'       => __('delete template', 'wprne'),
            'deleteTemplateHeader' => __('Delete Template', 'wprne'),
            'save'                 => __('Save', 'wprne'),
            'pageName'             => __('Page Name', 'wprne'),
            'pageTemplates'        => __('Page Templates', 'wprne'),
            'showHeaderBar'        => __('Show Header Bar', 'wprne'),
            'addBottomTab'         => __('Add to Bottom Tab Navigation', 'wprne'),
            'priority'             => __('Priority', 'wprne'),
            'inactiveColor'        => __('Inactive Color', 'wprne'),
            'addNewPage'           => __('add new page', 'wprne'),
            'addPageDialog'        => __('Add New Page', 'wprne'),
            'deletePageDialog'     => __('Delete Page', 'wprne'),
            'deletePage'           => __('delete page', 'wprne'),
            'duplicatePage'        => __('duplicate selected page', 'wprne'),
            'pageName'             => __('Page Name', 'wprne'),
            'selectedPageSetting'  => __('selected page settings', 'wprne'),
            'toolbarAlert'         => __('There is no page. Add a new page from the pages panel on the left.', 'wprne'),
            'errorSavePages'       => __('Something went wrong trying to save pages', 'wprne'),
            'successSavePages'     => __('Pages has been saved', 'wprne'),
            'previewMode'          => __('Preview Mode', 'wprne'),
            'builderMode'          => __('Builder Mode', 'wprne'),
            'layers'               => __('Layers', 'wprne'),
            'pages'                => __('Pages', 'wprne'),
            'templates'            => __('Templates', 'wprne'),
            'layerPanelAlert'      => __('Layers panel only available in builder mode', 'wprne'),
            'templatePanelAlert'   => __('Templates panel only available in builder mode', 'wprne'),
            'savePages'            => __('Save Pages', 'wprne'),
            'saving'               => __('Saving...', 'wprne'),
            'move'                 => __('move', 'wprne'),
            'horizontal'           => __('Horizontal', 'wprne'),
            'vertical'             => __('Vertical', 'wprne'),
            'solid'                => __('Solid', 'wprne'),
            'clear'                => __('Clear', 'wprne'),
            'outline'              => __('Outline', 'wprne'),
            'navigate'             => __('navigate', 'wprne'),
            'goBack'               => __('go back', 'wprne'),
            'atcAction'            => __('add to cart', 'wprne'),
            'toCheckout'           => __('to checkout', 'wprne'),
            'addQty'               => __('add cart item quantity', 'wprne'),
            'reduceQty'            => __('reduce cart item quantity', 'wprne'),
            'yes'                  => __('Yes', 'wprne'),
            'no'                   => __('No', 'wprne'),
            'wooProducts'          => __('Woocommerce Products', 'wprne'),
            'wooOrders'            => __('Woocommerce Orders', 'wprne'),
            'descending'           => __('Descending', 'wprne'),
            'ascending'            => __('Ascending', 'wprne'),
            'date'                 => __('Date', 'wprne'),
            'postId'               => __('Post Id', 'wprne'),
            'modified'             => __('Modified', 'wprne'),
            'all'                  => __('All', 'wprne'),
            'selectImage'          => __('Select image', 'wprne'),
            'changeImage'          => __('Change image', 'wprne'),
            'postImage'            => __('Post featured image', 'wprne'),
            'static'               => __('static', 'wprne'),
            'orderId'              => __('order number', 'wprne'),
            'orderStatus'          => __('order status', 'wprne'),
            'orderItem'            => __('order item', 'wprne'),
            'orderTotal'           => __('order total', 'wprne'),
            'shippingTotal'        => __('shipping total', 'wprne'),
            'paymentMethod'        => __('payment method', 'wprne'),
            'totalTax'             => __('total tax', 'wprne'),
            'currencySymbol'       => __('currency symbol', 'wprne'),
            'excerpt'              => __('Excerpt', 'wprne'),
            'content'              => __('Content', 'wprne'),
            'singlePost'           => __('Single Post', 'wprne'),
            'searchPost'           => __('Search Post', 'wprne'),
            'wooSingleProduct'     => __('Woocommerce Single Product', 'wprne'),
            'wooImageCarousel'     => __('Product Image Carousel', 'wprne'),
            'wooCart'              => __('Woocommerce Cart', 'wprne'),
            'cartItem'             => __('Cart Item', 'wprne'),
            'checkoutNative'       => __('Woocommerce Checkout', 'wprne'),
            'checkoutWebView'      => __('Checkout Web View', 'wprne'),
            'youtubePlayer'        => __('Youtube Player', 'wprne'),
            'selectComponent'      => __('Select Component', 'wprne'),
            'relative'             => __('Relative', 'wprne'),
            'absolute'             => __('Absolute', 'wprne'),
            'blank'                => __('Blank', 'wprne'),
            'wooShop'              => __('Woocommerce Shop', 'wprne'),
            'singleProduct'        => __('Single Product', 'wprne'),
            'myOrder'              => __('My Order', 'wprne'),
            'searchProduct'        => __('Search Product', 'wprne'),
            'addTemplateError'     => __('Something went wrong trying to save template', 'wprne'),
            'addTemplateSuccess'   => __('Template has been saved', 'wprne'),
            'saveSettingsError'    => __('Something went wrong trying to save settings', 'wprne'),
            'saveSettingsSuccess'  => __('Settings has been saved', 'wprne'),
            'tutorials'            => __('Tutorials', 'wprne'),
        );
    }
}
