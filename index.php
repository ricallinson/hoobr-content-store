<?php
namespace php_require\hoobr_content_store;

class HoobrContentStore {

    private $prefix = "";

    private $ttl = 0;

    public function __construct($prefix, $ttl, $type) {

        global $require;

        $request = $require("php-http/request");
        $pathlib = $require("php-path");

        $store = $require($type);
        $this->store = $store($pathlib->join($request->cfg("datroot"), $prefix));

        $this->prefix = "hoobr-content-store" . $prefix . "-";

        $this->ttl = $ttl;

        if (!function_exists("apc_store")) {
            $this->ttl = 0;
        }
    }

    public function put($key, $val) {

        $fullkey = $this->prefix . $key;

        $status = $this->store->put($key, $val);

        if ($status && $this->ttl) {
            apc_store($fullkey, $val, $this->ttl);
        }

        return $status;
    }

    public function get($key) {

        $fullkey = $this->prefix . $key;

        if ($this->ttl) {
            $status = false;
            $val = apc_fetch($fullkey, $status);
            if ($status) {
                return $val;
            }
        }

        $val = $this->store->get($key);

        if ($this->ttl) {
            $this->put($key, $val);
        }

        return $val;
    }

    public function delete($key) {

        $fullkey = $this->prefix . $key;

        if ($this->ttl) {
            apc_delete($fullkey);
        }

        return $this->store->delete($key);
    }

    public function getKeys($from = 0, $length = null, $filters = array()) {
        return $this->store->getKeys($from, $length, $filters);
    }
}

$module->exports = function ($prefix, $ttl = 0, $type = "php-keyval") {
    return new HoobrContentStore($prefix, $ttl, $type);
};
