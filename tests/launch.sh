#!/bin/sh


command -v pdfinfo > /dev/null
if [ $? -gt 0 ]; then
    echo "pdfinfo could not be found"
    echo "On Debian based systems you can run: apt install -y poppler-utils"
    exit 1
fi

# Only start here, the command checking can exit code > 0
set -eu

EXAMPLE_FILES="$(find examples/ -type f -name 'example*.php' \
                -not -path '*/barcodes/*' \
                -not -wholename 'examples/example_006.php' \
                | sort -df)"

EXAMPLE_BARCODE_FILES="$(find examples/barcodes -type f -name 'example*.php' \
                | sort -df)"

TEMP_FOLDER="$(mktemp -d /tmp/TCPDF-tests.XXXXXXXXX)"
OUTPUT_FILE="${TEMP_FOLDER}/output.pdf"
OUTPUT_FILE_ERROR="${TEMP_FOLDER}/errors.txt"
# Allows you to use PHP_BINARY="php8.1" ./tests/launch.sh
PHP_BINARY="${PHP_BINARY:-php}"
ROOT_DIR="$(${PHP_BINARY} -r 'echo realpath(__DIR__);')"
TESTS_DIR="${ROOT_DIR}/tests/"

PHP_EXT_DIR="$(${PHP_BINARY} -r 'echo ini_get("extension_dir");')"

echo "php extension dir: ${PHP_EXT_DIR}"

BCMATH_EXT="-d extension=$(find ${PHP_EXT_DIR} -type f -name 'bcmath.so')"
echo "bcmath found at: ${BCMATH_EXT}"

CURL_EXT="-d extension=$(find ${PHP_EXT_DIR} -type f -name 'curl.so')"
echo "curl found at: ${CURL_EXT}"

COVERAGE_EXTENSION="-d extension=pcov.so"
IMAGICK_OR_GD="-dextension=gd.so"
JSON_EXT="-dextension=json.so"
XML_EXT="-dextension=xml.so"
if [ "$(${PHP_BINARY} -r 'echo PHP_MAJOR_VERSION;')" = "5" ];then
    X_DEBUG_EXT="$(find ${PHP_EXT_DIR} -type f -name 'xdebug.so' || '')"
    echo "Xdebug found at: ${X_DEBUG_EXT}"
    # pcov does not exist for PHP 5
    COVERAGE_EXTENSION="-d zend_extension=${X_DEBUG_EXT} -d xdebug.mode=coverage"

    # 5.5, 5.4, 5.3
    if [ "$(${PHP_BINARY} -r 'echo (PHP_MINOR_VERSION < 6) ? "true" : "false";')" = "true" ];then
        IMAGICK_OR_GD="-dextension=imagick.so"
    fi

fi

if [ "$(${PHP_BINARY} -r 'echo PHP_MAJOR_VERSION.PHP_MINOR_VERSION;')" = "70" ];then
    X_DEBUG_EXT="$(find ${PHP_EXT_DIR} -type f -name 'xdebug.so' || '')"
    echo "Xdebug found at: ${X_DEBUG_EXT}"
    # pcov does not exist for PHP 7.0
    COVERAGE_EXTENSION="-d zend_extension=${X_DEBUG_EXT} -d xdebug.mode=coverage"
fi

# PHP >= 8.x.x
if [ "$(${PHP_BINARY} -r 'echo (PHP_MAJOR_VERSION >= 8) ? "true" : "false";')" = "true" ];then
    # The json ext is bundled into PHP 8.0
    JSON_EXT=""
fi

echo "Root folder: ${ROOT_DIR}"
echo "Temporary folder: ${TEMP_FOLDER}"
echo "PHP version: $(${PHP_BINARY} -v)"

FAILED_FLAG=0

cd "${ROOT_DIR}/examples"

for file in $EXAMPLE_FILES; do
    echo "File: $file"
    ${PHP_BINARY} -l "${ROOT_DIR}/$file" > /dev/null
    if [ $? -eq 0 ]; then
        echo "File-lint-passed: $file"
    fi
    set +e
    # Some examples load a bit more into memory (this is why the limit is set to 1G)
    # Avoid side effects on classes installed on the system, set include_path to a folder wihout php classes (include_path)
    ${PHP_BINARY} -n \
        -d include_path="${TEMP_FOLDER}" \
        -d date.timezone=UTC \
        ${IMAGICK_OR_GD} ${COVERAGE_EXTENSION} \
        ${BCMATH_EXT} \
        ${CURL_EXT} \
        ${JSON_EXT} \
        ${XML_EXT} \
        -d display_errors=on \
        -d error_reporting=-1 \
        -d memory_limit=1G \
        -d pcov.directory="${ROOT_DIR}" \
        -d auto_prepend_file="${TESTS_DIR}/coverage.php" \
        "${ROOT_DIR}/$file" 1> "${OUTPUT_FILE}" 2> "${OUTPUT_FILE_ERROR}"
    set -e
    if [ $? -eq 0 ]; then
        echo "File-run-passed: $file"
        ERROR_LOGS="$(cat "${OUTPUT_FILE_ERROR}")"
        if [ ! -z "${ERROR_LOGS}" ]; then
            FAILED_FLAG=1
            echo "Logs: $file"
            echo "---------------------------"
            echo "${ERROR_LOGS}"
            echo "---------------------------"
        fi
        if [ $(head -c 4 "${OUTPUT_FILE}") != "%PDF" ]; then
            FAILED_FLAG=1
            # cut before the PDF output starts and destroys the final logs
            OUT_LOGS="$(cat "${OUTPUT_FILE}" | sed '/%PDF/q')"
            echo "Generated-not-a-pdf: $file"
            echo "Logs (cut before PDF output eventually starts): $file"
            echo "---------------------------"
            echo "${OUT_LOGS}"
            echo "---------------------------"
            continue
        fi
        pdfinfo "${OUTPUT_FILE}" > /dev/null
        if [ $? -gt 0 ]; then
            FAILED_FLAG=1
            echo "Generated-invalid-file: $file"
        fi
        if [ "$file" = "examples/example_065.php" ] || [ "$file" = "examples/example_066.php" ]; then
          VALIDATION_OUTPUT="$(docker run -v $TEMP_FOLDER:/data --quiet --rm -w /data/ pdfix/verapdf-validation:latest validate --format 'json' -i 'output.pdf')"
          VALIDATION_RESULT="$(echo $VALIDATION_OUTPUT |  jq '.report.jobs[0].validationResult[0].compliant')"
          if [ "$VALIDATION_RESULT" = "false" ]; then
              FAILED_FLAG=1
              echo "Generated pdf file failed validation: $file"
              echo $VALIDATION_OUTPUT
          else
            VALIDATION_PROFILE="$(echo $VALIDATION_OUTPUT |  jq '.report.jobs[0].validationResult[0].profileName')"
            echo "Pdf validated with $VALIDATION_PROFILE: $file"
          fi
        fi
    else
        FAILED_FLAG=1
        echo "File-run-failed: $file"
        ERROR_LOGS="$(cat "${OUTPUT_FILE_ERROR}")"
        if [ ! -z "${ERROR_LOGS}" ]; then
            echo "Logs: $file"
            echo "---------------------------"
            echo "${ERROR_LOGS}"
            echo "---------------------------"
        else
            # cut before the PDF output starts and destroys the final logs
            OUT_LOGS="$(cat "${OUTPUT_FILE}" | sed '/%PDF/q')"
            echo "Logs: $file"
            echo "---------------------------"
            echo "${OUT_LOGS}"
            echo "---------------------------"
        fi
    fi
done

for file in $EXAMPLE_BARCODE_FILES; do
    echo "File: $file"
    ${PHP_BINARY} -l "${ROOT_DIR}/$file" > /dev/null
    if [ $? -eq 0 ]; then
        echo "File-lint-passed: $file"
    fi
    set +e
    # Avoid side effects on classes installed on the system, set include_path to a folder wihout php classes (include_path)
    ${PHP_BINARY} -n \
        -d include_path="${TEMP_FOLDER}" \
        -d date.timezone=UTC \
        ${BCMATH_EXT} \
        ${CURL_EXT} \
        ${COVERAGE_EXTENSION} \
        -d display_errors=on \
        -d error_reporting=-1 \
        -d pcov.directory="${ROOT_DIR}" \
        -d auto_prepend_file="${TESTS_DIR}/coverage.php" \
        "${ROOT_DIR}/$file" 1> "${OUTPUT_FILE}" 2> "${OUTPUT_FILE_ERROR}"
    set -e
    if [ $? -eq 0 ]; then
        echo "File-run-passed: $file"
        ERROR_LOGS="$(cat "${OUTPUT_FILE_ERROR}")"
        if [ ! -z "${ERROR_LOGS}" ]; then
            FAILED_FLAG=1
            echo "Logs: $file"
            echo "---------------------------"
            echo "${ERROR_LOGS}"
            echo "---------------------------"
        fi
    else
        FAILED_FLAG=1
        echo "File-run-failed: $file"
        ERROR_LOGS="$(cat "${OUTPUT_FILE_ERROR}")"
        if [ ! -z "${ERROR_LOGS}" ]; then
            echo "Logs: $file"
            echo "---------------------------"
            echo "${ERROR_LOGS}"
            echo "---------------------------"
        fi
        # cut before the PDF output starts and destroys the final logs
        OUT_LOGS="$(cat "${OUTPUT_FILE}" | sed '/%PDF/q')"
        if [ ! -z "${OUT_LOGS}" ]; then
            echo "Logs (cut before PDF output eventually starts): $file"
            echo "---------------------------"
            echo "${OUT_LOGS}"
            echo "---------------------------"
        fi
    fi
done

cd - > /dev/null

rm -rf "${TEMP_FOLDER}"

echo "Exit code: ${FAILED_FLAG}"
exit "${FAILED_FLAG}"
