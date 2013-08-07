<?php

error_reporting(E_ALL);
ini_set('display_errors', 'on');

/*
    Mock APC.
*/

if (!function_exists("apc_store")) {

    $apc = array();

    function apc_store($fullkey, $val, $ttl) {
        global $apc;
        $apc[$fullkey] = $val;
    }

    function apc_fetch($fullkey, &$success) {
        global $apc;
        if (isset($apc[$fullkey])) {
            $success = true;
            return $apc[$fullkey];
        }
        $success = false;
        return null;
    }

    function apc_delete($fullkey) {
        global $apc;
        unset($apc[$fullkey]);
    }

    function apc_clear_cache() {
        global $apc;
        $apc = array();
    }
}

/*
    Now we "require()" the file to test.
*/

$module = new stdClass();
require(__DIR__ . "/../index.php");

/*
    Now we test it.
*/

describe("hoobr-content-store", function () use ($module) {

    /*
        Set the data root folder in the request.

        This is the fixtures folder.
    */

    global $require;

    $require("php-http/request")->cfg("datroot", __DIR__ . "/fixtures");

    /*
        Test the basic store.
    */

    describe("store", function () use ($module) {

        it("should call store.put(), store.get(), store.delete() with no cache", function () use ($module) {

            $func = $module->exports;

            $store = $func("tmp");
            $key = "key";
            $val = "val";

            $store->put($key, $val);

            assert($store->get($key) === $val);

            $store->delete($key);

            assert($store->get($key) === null);
        });

        it("should call store.put(), store.get(), store.delete() with a 10 second cache", function () use ($module) {

            $func = $module->exports;

            $store = $func("tmp", 10);
            $key = "key1";
            $val = "val1";

            $store->put($key, $val);

            assert($store->get($key) === $val);

            apc_clear_cache();

            assert($store->get($key) === $val);

            $store->delete($key);

            assert($store->get($key) === null);
        });

        it("should return [10]", function () use ($module) {

            $func = $module->exports;
            $store = $func("range");

            $keys = $store->getKeys();

            assert(count($keys) === 10);
        });

        it("should return [5]", function () use ($module) {

            $func = $module->exports;
            $store = $func("range");

            $keys = $store->getKeys(5);

            assert(count($keys) === 5);
        });

        it("should return [1]", function () use ($module) {

            $func = $module->exports;
            $store = $func("range");

            $keys = $store->getKeys(0, 1);

            assert(count($keys) === 1);
        });

        it("should return [2]", function () use ($module) {

            $func = $module->exports;
            $store = $func("range");

            $keys = $store->getKeys(8, 2);

            assert(count($keys) === 2);
        });

        it("should return [3]", function () use ($module) {

            $func = $module->exports;
            $store = $func("range");

            $keys = $store->getKeys(5, 3);

            assert(count($keys) === 3);
        });

        it("should return [null] when trying to get() nothing", function () use ($module) {

            $func = $module->exports;
            $store = $func("range");

            $result = $store->get("null");

            assert($result === null);
        });

        it("should return [true] when trying to delete() nothing", function () use ($module) {

            $func = $module->exports;
            $store = $func("range");

            $result = $store->delete("null");

            assert($result === true);
        });
    });
    
    /*
        Check that what is put is got.
    */

    describe("store chars check", function () use ($module) {

        it("should return ", function () use ($module) {

            $func = $module->exports;

            $store = $func("tmp");
            $key = "charkey";
            $val = "(“foo“)";

            $store->put($key, $val);

            assert($store->get($key) === $val);

            $store->delete($key);

            assert($store->get($key) === null);
        });
    });
});
