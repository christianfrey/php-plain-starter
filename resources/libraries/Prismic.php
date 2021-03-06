<?php

include_once(__DIR__.'/../../vendor/autoload.php');

use Prismic\Api;

class Context
{
    private $api;
    private $ref;
    private $maybeAccessToken;

    public function __construct($api, $ref, $maybeAccessToken=null, $linkResolver=null)
    {
        $this->api = $api;
        $this->ref = $ref;
        $this->maybeAccessToken = $maybeAccessToken;
    }

    public function maybeRef()
    {
        if ($this->ref != $this->api->master()->ref) {
            return $this->ref;
        } else {
            return null;
        }
    }

    public function hasPrivilegedAccess()
    {
        return isset($this->maybeAccessToken);
    }

    public function getApi()
    {
        return $this->api;
    }

    public function getRef()
    {
        return $this->ref;
    }

    public function getAccessToken()
    {
        return $this->maybeAccessToken;
    }
}

class Prismic
{

    private static function array_get($path, $array)
    {
        if (empty($path)) {
            return $array;
        } elseif (empty($array)) {
            return null;
        } else {
            $key = array_shift($path);
            if (!isset($array[$key])) {
                return null;
            }

            return self::array_get($path, $array[$key]);
        }
    }

    public static function config($key)
    {
        $path = explode('.', $key);
        global $CONFIG;
        $value = self::array_get($path, $CONFIG);
        if (isset($value)) {
            return $value;
        } else {
            return null;
        }
    }

    public static function callback()
    {
        $maybeReferer = isset(getallheaders()['Referer']) ? getallheaders()['Referer'] : null;

        return Routes::authCallback(null, isset($maybeReferer) ? $maybeReferer : Routes::index());
    }

    public static function context()
    {
        $maybeAccessToken = isset($_COOKIE["ACCESS_TOKEN"]) ? $_COOKIE["ACCESS_TOKEN"] : self::config('prismic.token');
        $api = self::apiHome($maybeAccessToken);
        $ref = isset($_GET["ref"]) ? $_GET["ref"] : $api->master()->getRef();

        return new Context($api, $ref, $maybeAccessToken);
    }

    public static function apiHome($maybeAccessToken = null)
    {
        return Api::get(self::config('prismic.api'), $maybeAccessToken);
    }

    public static function getDocument($id)
    {
        $ctx = self::context();
        $documents = $ctx->getApi()->forms()->everything->query('[[:d = at(document.id, "'. $id .'")]]')->ref($ctx->getRef())->submit();

        return $documents[0];
    }
}
