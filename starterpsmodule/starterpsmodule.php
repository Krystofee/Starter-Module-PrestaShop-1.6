<?php
/**
 * Starter Module
 * 
 *  @author    PremiumPresta <office@premiumpresta.com>
 *  @copyright 2015 PremiumPresta
 *  @license   http://creativecommons.org/licenses/by/4.0/ CC BY 4.0
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class StarterPsModule extends Module
{
    /** @var Use to store the configuration from database */
    public $config_values;

    public function __construct()
    {
        $this->name = strtolower(get_class($this)); // internal identifier, unique and lowercase
        $this->tab = 'front_office_features'; // backend module coresponding category
        $this->version = '0.0.1'; // version number for the module
        $this->author = 'PremiumPresta'; // module author
        $this->need_instance = 0; // load the module when displaying the "Modules" page in backend
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Starter PrestaShop Module'); // public name
        $this->description = $this->l('Starter Module for PrestaShop 1.6.x'); // public description

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?'); // confirmation message at uninstall

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    /**
     * Install this module
     * @return boolean
     */
    public function install()
    {
        include dirname(__FILE__) . '/sql/install.php';

        return parent::install() &&
                $this->initConfig() &&
                $this->registerHook('actionAdminControllerSetMedia') &&
                $this->registerHook('header') &&
                $this->registerHook('displayHome');
    }

    /**
     * Uninstall this module
     * @return boolean
     */
    public function uninstall()
    {
        include dirname(__FILE__) . '/sql/uninstall.php';

        return Configuration::deleteByName($this->name) &&
                parent::uninstall();
    }

    /**
     * Add the CSS & JavaScript files you want to be loaded in BO.
     */
    public function hookActionAdminControllerSetMedia()
    {
        if ($this->isConfigPage()) {
            $this->context->controller->addJS($this->_path . 'views/js/configuration.js');
            $this->context->controller->addCSS($this->_path . 'views/css/configuration.css');
        }
    }
    
    /**
     * Set the default configuration
     * @return boolean
     */
    protected function initConfig()
    {
        $config = array();

        $languages = Language::getLanguages(false);
        foreach ($languages as $lang) {
            $config['quote'][$lang['id_lang']] = 'The secret of getting ahead is getting started. The secret of getting started is breaking your complex overwhelming tasks into small manageable tasks, and then starting on the first one.';
        }

        $config['author'] = 'Mark Twain';
        $config['show_author'] = true;

        return $this->setConfigValues($config);
    }

    /**
     * Configuration page
     */
    public function getContent()
    {
        $this->config_values = $this->getConfigValues(); 
        
        $this->context->smarty->assign(array(
            'module' => array(
                'class' => get_class($this),
                'name' => $this->name,
                'displayName' => $this->displayName,
                'dir' => $this->_path
            )
        ));

        return $this->postProcess() . $this->renderForm();
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Starter PrestaShop Module'),
                    'icon' => 'icon-cogs'
                ),
                'input' => array(
                    array(
                        'label' => $this->l('Quote'),
                        'name' => 'quote',
                        'type' => 'textarea',
                        'cols' => 10,
                        'rows' => 10,
                        'autoload_rte' => true,
                        'lang' => true,
                        'required' => true
                    ),
                    array(
                        'label' => $this->l('Author'),
                        'name' => 'author',
                        'type' => 'text',
                        'class' => 'fixed-width-lg',
                    ),
                    array(
                        'label' => $this->l('Show Author'),
                        'name' => 'show_author',
                        'type' => 'switch',
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Yes')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('No')
                            )
                        ),
                    ),
                ),
                'submit' => array(
                    'name' => 'saveBtn',
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-success pull-right'
                )
            )
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        if (Tools::isSubmit('saveBtn')) {
            $config = $this->getConfigValues();

            $languages = Language::getLanguages();
            foreach ($languages as $lang) {
                $config['quote'][$lang['id_lang']] = Tools::getValue('quote_' . $lang['id_lang']);
            }

            $config['author'] = Tools::getValue('author');
            $config['show_author'] = Tools::getValue('show_author');
            $this->setConfigValues($config);

            return $this->displayConfirmation($this->l('Settings updated'));
        }
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Get configuration array from database
     * @return array
     */
    public function getConfigValues()
    {
        return json_decode(Configuration::get($this->name), true);
    }

    /**
     * Set configuration array to database
     * @param array $config 
     * @param bool $merge when true, $config can be only a subset to modify or add additional fields
     * @return array
     */
    public function setConfigValues($config, $merge = false)
    {
        if ($merge) {
            $config = array_merge($this->getConfigValues(), $config);
        }

        if (Configuration::updateValue($this->name, json_encode($config))) {
            return $config;
        }

        return false;
    }
    
    /**
     * Determins if on the module configuration page
     * @return bool
     */
    public function isConfigPage()
    {
        return self::isAdminPage('modules') && Tools::getValue('configure') === $this->name;
    }
    
    /**
     * Determines if on the specified admin page
     * @param string $page
     * @return bool
     */
    public static function isAdminPage($page)
    {
        return Tools::getValue('controller') === 'Admin' . ucfirst($page);
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path . '/views/js/front.js');
        $this->context->controller->addCSS($this->_path . '/views/css/front.css');
    }

    /**
     * Homepage content hook (Technical name: displayHome)
     */
    public function hookDisplayHome($params)
    {
        !isset($params['tpl']) && $params['tpl'] = 'displayHome';

        $config = $this->getConfigValues();
        $this->smarty->assign($config);

        return $this->display(__FILE__, $params['tpl'] . '.tpl');
    }
}
