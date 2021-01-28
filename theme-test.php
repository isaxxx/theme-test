<?php
/*
Plugin Name: Theme Test
Description: テスト環境構築用のWordPressプラグインです。任意のユーザーを作成し、テスト用テーマを選択することで、そのユーザーでテスト用テーマを閲覧することが可能です。
Author: Isaka Masahide
Version: 1.0.0
*/


if (!defined('ABSPATH')) {
	die('-1');
}

if (!class_exists('MT_THEME_TEST')) {
  class MT_THEME_TEST {
    public static function getInstance () {
      static $instance = null;
      if (null === $instance) {
        $instance = new self();
      }
      return $instance;
    }

  	public static function getPluginVersion () {
      $fileData = get_file_data(__FILE__, array('VERSION'));
  		return $fileData[0];
  	}

  	public static function getPluginID () {
  		return mb_strtolower(__CLASS__);
  	}

    public static function getPluginName () {
      $fileData = get_file_data(__FILE__, array('PLUGIN NAME'));
      return $fileData[0];
    }

    public static function getClassName () {
      return get_called_class();
    }

  	public static function getPluginPath () {
  		return plugin_dir_path(__FILE__);
  	}

  	public static function getPluginURL () {
  		return plugins_url('', __FILE__);
  	}

  	function __construct () {
  		register_activation_hook(__FILE__, array(&$this, 'activate'));
      register_deactivation_hook(__FILE__, array(&$this, 'deactivate'));
      //register_uninstall_hook(__FILE__, array(&$this, 'uninstall'));
      add_action('admin_menu', array(&$this, 'addPage'));
      add_filter('template', array(&$this, 'change_theme'));
      add_filter('stylesheet', array(&$this, 'change_theme'));
      add_action('wp_head', array(&$this, 'add_noindex'));

      if (isset($_REQUEST['page'] ) && $_REQUEST['page'] === self::getPluginID()) {
        add_action('admin_notices', array(&$this, 'admin_notices'));
        add_action('admin_init', array(&$this, 'admin_init'));
      }
  	}

    public function activate () {
      // activation hook
    }

    public function deactivate () {
      // deactivation hook
    }

    public function uninstall () {
      // deactivation hook
    }

    public function add_noindex () {
      if (isset($_GET['__test']) && $_GET['__test']) {
        echo '<meta name="robots" content="noindex, nofollow" />';
      }
    }

    public function change_theme ($template) {
      $user = wp_get_current_user();
      if (substr($user->user_login, 0, 7) === '__test-') {
        $options = get_option(self::getPluginID());
        if ($options && isset($options['theme']) && !empty($options['theme'])) {
          return $options['theme'];
        } else {
          return $template;
        }
      } elseif (isset($_GET['__test']) && $_GET['__test']) {
        return $_GET['__test'];
      } else {
        return $template;
      }
    }

    public function addPage () {
      add_options_page(self::getPluginName(), self::getPluginName(), 'edit_themes', self::getPluginID(), array(&$this, 'createPage'));
    }

    public function createPage () {
      $options = get_option(self::getPluginID());
      $selectedTheme = null;
      if ($options && isset($options['theme']) && !empty($options['theme'])) {
        $selectedTheme = $options['theme'];
      }
      ?>
        <div class="wrap">
          <h2>テスト用テーマの指定</h2>
          <p>テストで使用するテーマを選択してください。<br />設定したテーマはユーザーIDが「__test-」から始まるユーザーでログインするか、あるいはGETパラメーター「__test=（テーマの名前）」を付与すると閲覧できます。</p>
          <form method="post" action="">
            <?php wp_nonce_field(self::getPluginID(), '_wpnonce'); ?>
            <label>
              <span>テストで使用するテーマ</span>
              <?php $themes = wp_get_themes(); ?>
              <select name="theme">
                <option value="">選択してください</option>
                <?php foreach ($themes as $theme): ?>
                  <option value="<?php echo $theme->get_stylesheet(); ?>" <?php if ($selectedTheme === $theme->get_stylesheet()): ?>selected="selected"<?php endif; ?>><?php echo $theme->get_stylesheet(); ?></option>
                <?php endforeach; ?>
              </select>
            </label>
            <p class="submit">
              <input type="submit" name="submit" value="設定を保存する" class="button-primary" />
            </p>
          </form>
        </div>
      <?php
    }

    public function admin_init() {
      if (isset($_POST['_wpnonce']) && $_POST['_wpnonce']) {
        $errors = new WP_Error();
        $updates = new WP_Error();

        if (check_admin_referer(self::getPluginID(), '_wpnonce')) { // nonceチェックは、管理画面では check_admin_referer で公開側は wp_verify_nonce を使用する
          $theme = esc_html($_POST['theme']);
          $options = get_option(self::getPluginID());
          $options['theme'] = $theme;
          update_option(self::getPluginID(), $options);
          $updates->add('update', '保存しました。');
          set_transient(self::getPluginID() . '-updates', $updates->get_error_messages(), 1);
        } else {
          $errors->add('error', '不正なリクエストです。');
          set_transient(self::getPluginID() . '-errors', $errors->get_error_messages(), 1);
        }
      }
    }

    public function admin_notices() {
      ?>
        <?php if ($messages = get_transient(self::getPluginID() . '-updates')): ?>
          <div class="updated">
            <ul>
              <?php foreach ($messages as $key => $message): ?>
                <li><?php echo esc_html($message); ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>
        <?php if ($messages = get_transient(self::getPluginID() . '-errors')): ?>
          <div class="error">
            <ul>
              <?php foreach ($messages as $key => $message): ?>
                <li><?php echo esc_html($message); ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>
      <?php
    }
  }

  global $MT_THEME_TEST;
  $MT_THEME_TEST = MT_THEME_TEST::getInstance();
}
