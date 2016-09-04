<?php

var_dump(file_put_contents(__DIR__.'/1.txt', 'data'));

var_dump(unlink(__DIR__.'/1.txt'));
