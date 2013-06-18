<?php
/**
 * JsTrans
 *
 * Use Yii translations in Javascript
 *
 */

/**
 * Publish translations in JSON and append to the page
 *
 * @param mixed $categories the categories that are exported (accepts array and string)
 * @param mixed $languages the languages that are exported (accepts array and string)
 * @param string $defaultLanguage the default language used in translations
 */
class JsTrans
{
    private $_assetsPath;
    private $_publishPath;
    private $_publishUrl;

    public function __construct($categories, $languages, $defaultLanguage = null)
    {
        $assetManager = Yii::app()->assetManager;

        // set default language
        if (!$defaultLanguage) $defaultLanguage = Yii::app()->language;

        // create arrays from params
        if (!is_array($categories)) $categories = array($categories);
        if (!is_array($languages)) $languages = array($languages);

        // set paths
        $this->_assetsPath = dirname(__FILE__) . '/assets';
        $this->_publishPath = $assetManager->getPublishedPath($this->_assetsPath);

        // publish assets and generate dictionary file if neccessary
        if (!file_exists($this->_publishPath) || YII_DEBUG) {
            $this->_publishPath = $assetManager->getPublishedPath($this->_assetsPath);

            // create hash
            $hash = substr(md5(implode($categories) . ':' . implode($languages) ), 0, 10);
            $dictionaryFile = "JsTrans.dictionary.{$hash}.js";

            // declare config (passed to JS)
            $config = array('language' => $defaultLanguage);

            // base folder for message translations
            $messagesFolder = rtrim(Yii::app()->messages->basePath, '\/');

            // loop message files and store translations in array
            $dictionary = array();
            foreach ($languages as $lang) {
                if (!isset($dictionary[$lang])) $dictionary[$lang] = array();

                foreach ($categories as $cat) {
                    $messagefile = $messagesFolder . '/' . $lang . '/' . $cat . '.php';
                    if (file_exists($messagefile)) {
                        $dictionary[$lang][$cat] = array_filter(require($messagefile));
                    }
                }
            }

            // JSONify config/dictionary
            $data = 'Yii.translate.config=' . CJSON::encode($config) . ';'
                  . 'Yii.translate.dictionary=' . CJSON::encode($dictionary);

            // save to dictionary file
            if (!file_put_contents($this->_publishPath . '/' . $dictionaryFile, $data)) {
               Yii::log('Error: Could not write dictionary file', 'trace', 'jstrans');
               return null;
            }
        }

        $this->_publishUrl = $assetManager->getPublishedUrl($this->_assetsPath);

        // register scripts
        Yii::app()->clientScript->registerScriptFile(
            $this->_publishUrl . '/JsTrans.min.js', CClientScript::POS_HEAD);
        Yii::app()->clientScript->registerScriptFile(
            $this->_publishUrl . '/' . $dictionaryFile, CClientScript::POS_HEAD);
    }
}
