<?php

namespace modules\tracking\models\backend;

use Yii;
use yii\base\Model;

/**
 * Class TrackingStorage
 * @package modules\tracking\models\backend
 *
 * @property array $sessionProvider
 * @property bool|mixed $locationFromTrackingStorage
 * @property array $cookieProvider
 */
class TrackingStorage extends Model
{
    /**
     *
     */
    const PROVIDER_KEY = 'tracking_storage';
    /**
     *
     */
    const IDENTIFIER_KEY = 'identifier';
    /**
     *
     */
    const HANDLER_KEY = 'handler';

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [

        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [

        ];
    }

    /**
     * @return array
     */
    public function get()
    {
        $data = [];
        $data['session_provider'] = self::getSessionProvider();
        $data['cookie_provider'] = self::getCookieProvider();

        return $data;
    }

    /**
     * @param array $pair
     */
    public function setSessionProvider(array $pair)
    {
        $session = Yii::$app->session;
        if (!empty($pair)) {
            $collection = [];
            foreach ($pair as $key => $value) {
                $collection[$key] = $value;
            }

            $data_in_storage = $session[self::PROVIDER_KEY];
            !is_array($data_in_storage) ? $data_in_storage = [] : null;
            $collection = array_merge($data_in_storage, $collection);
            $session->set(self::PROVIDER_KEY, $collection);
        }
    }

    /**
     * @param bool $key
     *
     * @return bool|mixed
     */
    public function getSessionProvider($key = false)
    {
        $session = Yii::$app->session;
        if (!$key) {
            $result = $session[self::PROVIDER_KEY];
        } else {
            if (!empty($session[self::PROVIDER_KEY][$key])) {
                $result = $session[self::PROVIDER_KEY][$key];
            }
        }

        return !empty($result) ? $result : false;
    }

    /**
     * @param bool $key
     */
    public function clearSessionProvider($key = false)
    {
        $session = Yii::$app->session;
        if ($session->isActive) {
            if (!$key) {
                $session->remove(self::PROVIDER_KEY);
            } else {
                unset($_SESSION[self::PROVIDER_KEY][$key]);
            }
        }
    }

    /**
     * @return bool|mixed
     */
    public function getLocationFromTrackingStorage()
    {
        return $this->getSessionProvider('users_geolocation');
    }

    /**
     * @param array $pair
     */
    public function setCookieProvider(array $pair)
    {
        if (!empty($pair)) {
            foreach ($pair as $key => $value) {
                if (!isset(Yii::$app->request->cookies[self::PROVIDER_KEY . '_' . $key])) {
                    Yii::$app->response->cookies->add(new \yii\web\Cookie([
                        'name' => self::PROVIDER_KEY . '_' . $key,
                        'value' => $value,
                    ]));
                }
            }
        }
    }

    /**
     * @return array
     */
    public function getCookieProvider()
    {
        $c = Yii::$app->request->cookies;
        $provider = [];
        foreach ($c as $cookie_name => $cookie) {
            if (substr($cookie_name, 0, strlen(self::PROVIDER_KEY)) === self::PROVIDER_KEY) {
                $data = $cookie_name;
                $key = substr($data, strpos($data, self::PROVIDER_KEY) + 1 + strlen(self::PROVIDER_KEY));
                $provider[$key] = $cookie->value;
            }
        }

        return $provider;
    }

    /**
     *
     */
    public function clearCookieProvider()
    {
        $c = Yii::$app->request->cookies;
        foreach ($c as $cookie_name => $cookie) {
            if (substr($cookie_name, 0, strlen(self::PROVIDER_KEY)) === self::PROVIDER_KEY) {
                $cr = Yii::$app->response->cookies;
                $cr->remove($cookie_name);
            }
        }
    }
}