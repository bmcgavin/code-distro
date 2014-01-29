<?php

namespace Codedistro;

interface Broker {

    function init($log);
    function connect($settings);
    function recv($selector);
    function send($selector, $message);

}
