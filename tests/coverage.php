<?php

if (extension_loaded('pcov')) {
    \pcov\start();


    class CoverageObjectPcov
    {

        public function __destruct()
        {
            \pcov\stop();
            $rootDir      = realpath(__DIR__ . '/../') . '/';
            $coverageFile = $rootDir . 'tests/coverage.lcov';
            $covData      = \pcov\collect(
                \pcov\exclusive,
                array(
                __FILE__
                )
            );
            $coverageData = '';
            foreach ($covData as $file => $coverageForFile) {
                $coverageData .= 'SF:' . $file . "\n";
                $coverageData .= 'TN:' . str_replace($rootDir, '', $_SERVER['PHP_SELF']) . "\n";
                foreach ($coverageForFile as $line => $coverageValue) {
                    $coverageValue = $coverageValue === -1 ? 0 : $coverageValue;
                    $coverageData .= 'DA:' . $line . ',' . $coverageValue . "\n";
                }
                $coverageData .= 'end_of_record' . "\n";
            }
            file_put_contents($coverageFile, $coverageData, LOCK_EX | FILE_APPEND);
        }

    }

    $a = new CoverageObjectPcov();
}
if (extension_loaded('xdebug')) {
    \xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);

    class CoverageObjectXdebug
    {

        public function __destruct()
        {
            $rootDir      = realpath(__DIR__ . '/../') . '/';
            $coverageFile = $rootDir . 'tests/coverage.lcov';
            $covData      = xdebug_get_code_coverage();
            $coverageData = '';
            foreach ($covData as $file => $coverageForFile) {
                $coverageData .= 'SF:' . $file . "\n";
                $coverageData .= 'TN:' . str_replace($rootDir, '', $_SERVER['PHP_SELF']) . "\n";
                foreach ($coverageForFile as $line => $coverageValue) {
                    $coverageValue = $coverageValue > 0 ? $coverageValue : 0;
                    $coverageData .= 'DA:' . $line . ',' . $coverageValue . "\n";
                }
                $coverageData .= 'end_of_record' . "\n";
            }
            file_put_contents($coverageFile, $coverageData, LOCK_EX | FILE_APPEND);
            \xdebug_stop_code_coverage(true);
        }

    }

    $a = new CoverageObjectXdebug();
}
