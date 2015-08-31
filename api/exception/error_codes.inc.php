<?php
/**
 * error_codes.inc.php
 * 
 * This file is where all error codes are defined.
 * All error code are named after the class and function they occur in.
 */

/**
 * Error number category defines.
 */
define( 'ARGUMENT_CENOZO_BASE_ERRNO',   100000 );
define( 'DATABASE_CENOZO_BASE_ERRNO',   200000 );
define( 'LDAP_CENOZO_BASE_ERRNO',       300000 );
define( 'NOTICE_CENOZO_BASE_ERRNO',     400000 );
define( 'PERMISSION_CENOZO_BASE_ERRNO', 500000 );
define( 'RUNTIME_CENOZO_BASE_ERRNO',    600000 );
define( 'SYSTEM_CENOZO_BASE_ERRNO',     700000 );
define( 'TEMPLATE_CENOZO_BASE_ERRNO',   800000 );

/**
 * "argument" error codes
 */
define( 'ARGUMENT__CENOZO_BUSINESS_DATA_MANAGER__IS_VALUE__ERRNO',
        ARGUMENT_CENOZO_BASE_ERRNO + 1 );
define( 'ARGUMENT__CENOZO_BUSINESS_DATA_MANAGER__GET_VALUE__ERRNO',
        ARGUMENT_CENOZO_BASE_ERRNO + 2 );
define( 'ARGUMENT__CENOZO_BUSINESS_DATA_MANAGER__GET_PARTICIPANT_VALUE__ERRNO',
        ARGUMENT_CENOZO_BASE_ERRNO + 3 );
define( 'ARGUMENT__CENOZO_BUSINESS_DATA_MANAGER__PARSE_KEY__ERRNO',
        ARGUMENT_CENOZO_BASE_ERRNO + 4 );
define( 'ARGUMENT__CENOZO_BUSINESS_LIMESURVEY_MANAGER__GET_VALUE__ERRNO',
        ARGUMENT_CENOZO_BASE_ERRNO + 5 );
define( 'ARGUMENT__CENOZO_BUSINESS_OPAL_MANAGER__GET_VALUE__ERRNO',
        ARGUMENT_CENOZO_BASE_ERRNO + 6 );
define( 'ARGUMENT__CENOZO_BUSINESS_OPAL_MANAGER__GET_LABEL__ERRNO',
        ARGUMENT_CENOZO_BASE_ERRNO + 7 );
define( 'ARGUMENT__CENOZO_BUSINESS_REPORT____CALL__ERRNO',
        ARGUMENT_CENOZO_BASE_ERRNO + 8 );
define( 'ARGUMENT__CENOZO_BUSINESS_SESSION__SET_SITE_AND_ROLE__ERRNO',
        ARGUMENT_CENOZO_BASE_ERRNO + 9 );
define( 'ARGUMENT__CENOZO_BUSINESS_SETTING_MANAGER__READ_SETTINGS__ERRNO',
        ARGUMENT_CENOZO_BASE_ERRNO + 10 );
define( 'ARGUMENT__CENOZO_BUSINESS_VOIP_CALL____CONSTRUCT__ERRNO',
        ARGUMENT_CENOZO_BASE_ERRNO + 11 );
define( 'ARGUMENT__CENOZO_BUSINESS_VOIP_MANAGER__CALL__ERRNO',
        ARGUMENT_CENOZO_BASE_ERRNO + 12 );
define( 'ARGUMENT__CENOZO_DATABASE_HAS_NOTE__MULTINOTE__ERRNO',
        ARGUMENT_CENOZO_BASE_ERRNO + 13 );
define( 'ARGUMENT__CENOZO_DATABASE_MODIFIER__JOIN_MODIFIER__ERRNO',
        ARGUMENT_CENOZO_BASE_ERRNO + 14 );
define( 'ARGUMENT__CENOZO_DATABASE_MODIFIER__WHERE__ERRNO',
        ARGUMENT_CENOZO_BASE_ERRNO + 15 );
define( 'ARGUMENT__CENOZO_DATABASE_MODIFIER__GROUP__ERRNO',
        ARGUMENT_CENOZO_BASE_ERRNO + 16 );
define( 'ARGUMENT__CENOZO_DATABASE_MODIFIER__HAVING__ERRNO',
        ARGUMENT_CENOZO_BASE_ERRNO + 17 );
define( 'ARGUMENT__CENOZO_DATABASE_MODIFIER__ORDER__ERRNO',
        ARGUMENT_CENOZO_BASE_ERRNO + 18 );
define( 'ARGUMENT__CENOZO_DATABASE_MODIFIER__LIMIT__ERRNO',
        ARGUMENT_CENOZO_BASE_ERRNO + 19 );
define( 'ARGUMENT__CENOZO_DATABASE_MODIFIER__OFFSET__ERRNO',
        ARGUMENT_CENOZO_BASE_ERRNO + 20 );
define( 'ARGUMENT__CENOZO_DATABASE_PARTICIPANT__MULTIEDIT__ERRNO',
        ARGUMENT_CENOZO_BASE_ERRNO + 21 );
define( 'ARGUMENT__CENOZO_DATABASE_POSTCODE__GET_MATCH__ERRNO',
        ARGUMENT_CENOZO_BASE_ERRNO + 22 );
define( 'ARGUMENT__CENOZO_DATABASE_RECORD____GET__ERRNO',
        ARGUMENT_CENOZO_BASE_ERRNO + 23 );
define( 'ARGUMENT__CENOZO_DATABASE_RECORD____SET__ERRNO',
        ARGUMENT_CENOZO_BASE_ERRNO + 24 );
define( 'ARGUMENT__CENOZO_DATABASE_RECORD__GET_COLUMN_VALUES__ERRNO',
        ARGUMENT_CENOZO_BASE_ERRNO + 25 );
define( 'ARGUMENT__CENOZO_DATABASE_RECORD____CALL__ERRNO',
        ARGUMENT_CENOZO_BASE_ERRNO + 26 );
define( 'ARGUMENT__CENOZO_DATABASE_RECORD__GET_RECORD_LIST__ERRNO',
        ARGUMENT_CENOZO_BASE_ERRNO + 27 );
define( 'ARGUMENT__CENOZO_DATABASE_RECORD__SELECT__ERRNO',
        ARGUMENT_CENOZO_BASE_ERRNO + 28 );
define( 'ARGUMENT__CENOZO_DATABASE_RECORD__GET_UNIQUE_RECORD__ERRNO',
        ARGUMENT_CENOZO_BASE_ERRNO + 29 );
define( 'ARGUMENT__CENOZO_DATABASE_RECORD__GET_DISTINCT_VALUES__ERRNO',
        ARGUMENT_CENOZO_BASE_ERRNO + 30 );
define( 'ARGUMENT__CENOZO_DATABASE_SELECT__ADD_TABLE_COLUMN__ERRNO',
        ARGUMENT_CENOZO_BASE_ERRNO + 31 );
define( 'ARGUMENT__CENOZO_DATABASE_SITE__ADD_ACCESS__ERRNO',
        ARGUMENT_CENOZO_BASE_ERRNO + 32 );
define( 'ARGUMENT__CENOZO_DATABASE_USER__ADD_ACCESS__ERRNO',
        ARGUMENT_CENOZO_BASE_ERRNO + 33 );
define( 'ARGUMENT__CENOZO_SERVICE_POST__EXECUTE__ERRNO',
        ARGUMENT_CENOZO_BASE_ERRNO + 34 );
define( 'ARGUMENT__CENOZO_SERVICE_SERVICE__GET_ARGUMENT__ERRNO',
        ARGUMENT_CENOZO_BASE_ERRNO + 35 );
define( 'ARGUMENT__CENOZO_SERVICE_STATUS__SET_CODE__ERRNO',
        ARGUMENT_CENOZO_BASE_ERRNO + 36 );

/**
 * "database" error codes
 * 
 * Since database errors already have codes this list is likely to stay empty.
 */

/**
 * "ldap" error codes
 * 
 * Since ldap errors already have codes this list is likely to stay empty.
 */

/**
 * "notice" error codes
 */
define( 'NOTICE__CENOZO_BOOTSTRAP__LAUNCH_API__ERRNO',
        NOTICE_CENOZO_BASE_ERRNO + 1 );
define( 'NOTICE__CENOZO_BUSINESS_CENOZO_MANAGER__SEND__ERRNO',
        NOTICE_CENOZO_BASE_ERRNO + 2 );
define( 'NOTICE__CENOZO_BUSINESS_OPAL_MANAGER__GET_VALUE__ERRNO',
        NOTICE_CENOZO_BASE_ERRNO + 3 );
define( 'NOTICE__CENOZO_BUSINESS_OPAL_MANAGER__GET_LABEL__ERRNO',
        NOTICE_CENOZO_BASE_ERRNO + 4 );
define( 'NOTICE__CENOZO_BUSINESS_SEMAPHORE__ACQUIRE__ERRNO',
        NOTICE_CENOZO_BASE_ERRNO + 5 );
define( 'NOTICE__CENOZO_BUSINESS_SEMAPHORE__GET_PROCESS_COUNT__ERRNO',
        NOTICE_CENOZO_BASE_ERRNO + 6 );
define( 'NOTICE__CENOZO_BUSINESS_SEMAPHORE__GET_VARIABLE__ERRNO',
        NOTICE_CENOZO_BASE_ERRNO + 7 );
define( 'NOTICE__CENOZO_BUSINESS_SEMAPHORE__SET_VARIABLE__ERRNO',
        NOTICE_CENOZO_BASE_ERRNO + 8 );
define( 'NOTICE__CENOZO_BUSINESS_SESSION__INITIALIZE__ERRNO',
        NOTICE_CENOZO_BASE_ERRNO + 9 );
define( 'NOTICE__CENOZO_BUSINESS_SESSION__SET_USER__ERRNO',
        NOTICE_CENOZO_BASE_ERRNO + 10 );
define( 'NOTICE__CENOZO_BUSINESS_VOIP_MANAGER__CALL__ERRNO',
        NOTICE_CENOZO_BASE_ERRNO + 11 );
define( 'NOTICE__CENOZO_DATABASE_ADDRESS__SAVE__ERRNO',
        NOTICE_CENOZO_BASE_ERRNO + 12 );
define( 'NOTICE__CENOZO_DATABASE_CONSENT__SAVE__ERRNO',
        NOTICE_CENOZO_BASE_ERRNO + 13 );
define( 'NOTICE__CENOZO_DATABASE_DATABASE__EXECUTE__ERRNO',
        NOTICE_CENOZO_BASE_ERRNO + 14 );
define( 'NOTICE__CENOZO_DATABASE_PHONE__SAVE__ERRNO',
        NOTICE_CENOZO_BASE_ERRNO + 15 );
define( 'NOTICE__CENOZO_DATABASE_SITE__SAVE__ERRNO',
        NOTICE_CENOZO_BASE_ERRNO + 16 );

/**
 * "permission" error codes
 */
define( 'PERMISSION__CENOZO_DATABASE_ACCESS__SAVE__ERRNO',
        PERMISSION_CENOZO_BASE_ERRNO + 1 );
define( 'PERMISSION__CENOZO_DATABASE_ACCESS__DELETE__ERRNO',
        PERMISSION_CENOZO_BASE_ERRNO + 2 );

/**
 * "runtime" error codes
 */
define( 'RUNTIME__CENOZO_BOOTSTRAP__LAUNCH_UI__ERRNO',
        RUNTIME_CENOZO_BASE_ERRNO + 1 );
define( 'RUNTIME__CENOZO_BUSINESS_CENOZO_MANAGER__PULL__ERRNO',
        RUNTIME_CENOZO_BASE_ERRNO + 2 );
define( 'RUNTIME__CENOZO_BUSINESS_CENOZO_MANAGER__SEND__ERRNO',
        RUNTIME_CENOZO_BASE_ERRNO + 3 );
define( 'RUNTIME__CENOZO_BUSINESS_LDAP_MANAGER__VALIDATE_USER__ERRNO',
        RUNTIME_CENOZO_BASE_ERRNO + 4 );
define( 'RUNTIME__CENOZO_BUSINESS_LDAP_MANAGER__SET_USER_PASSWORD__ERRNO',
        RUNTIME_CENOZO_BASE_ERRNO + 5 );
define( 'RUNTIME__CENOZO_BUSINESS_OPAL_MANAGER__GET_VALUE__ERRNO',
        RUNTIME_CENOZO_BASE_ERRNO + 6 );
define( 'RUNTIME__CENOZO_BUSINESS_OPAL_MANAGER__GET_LABEL__ERRNO',
        RUNTIME_CENOZO_BASE_ERRNO + 7 );
define( 'RUNTIME__CENOZO_BUSINESS_REPORT____CALL__ERRNO',
        RUNTIME_CENOZO_BASE_ERRNO + 8 );
define( 'RUNTIME__CENOZO_BUSINESS_REPORT__SET_CELL__ERRNO',
        RUNTIME_CENOZO_BASE_ERRNO + 9 );
define( 'RUNTIME__CENOZO_BUSINESS_REPORT__MERGE_CELLS__ERRNO',
        RUNTIME_CENOZO_BASE_ERRNO + 10 );
define( 'RUNTIME__CENOZO_BUSINESS_SEMAPHORE__RELEASE__ERRNO',
        RUNTIME_CENOZO_BASE_ERRNO + 11 );
define( 'RUNTIME__CENOZO_BUSINESS_SEMAPHORE__GET_VARIABLE__ERRNO',
        RUNTIME_CENOZO_BASE_ERRNO + 12 );
define( 'RUNTIME__CENOZO_BUSINESS_SEMAPHORE__SET_VARIABLE__ERRNO',
        RUNTIME_CENOZO_BASE_ERRNO + 13 );
define( 'RUNTIME__CENOZO_BUSINESS_SESSION__INITIALIZE__ERRNO',
        RUNTIME_CENOZO_BASE_ERRNO + 14 );
define( 'RUNTIME__CENOZO_BUSINESS_SETTING_MANAGER____CONSTRUCT__ERRNO',
        RUNTIME_CENOZO_BASE_ERRNO + 15 );
define( 'RUNTIME__CENOZO_BUSINESS_SURVEY_MANAGER__PROCESS_WITHDRAW__ERRNO',
        RUNTIME_CENOZO_BASE_ERRNO + 16 );
define( 'RUNTIME__CENOZO_BUSINESS_VOIP_MANAGER__INITIALIZE__ERRNO',
        RUNTIME_CENOZO_BASE_ERRNO + 17 );
define( 'RUNTIME__CENOZO_BUSINESS_VOIP_MANAGER__CALL__ERRNO',
        RUNTIME_CENOZO_BASE_ERRNO + 18 );
define( 'RUNTIME__CENOZO_BUSINESS_WITHDRAW_MANAGER__PROCESS__ERRNO',
        RUNTIME_CENOZO_BASE_ERRNO + 19 );
define( 'RUNTIME__CENOZO_DATABASE_APPLICATION__GET_URL__ERRNO',
        RUNTIME_CENOZO_BASE_ERRNO + 20 );
define( 'RUNTIME__CENOZO_DATABASE_DATABASE__GET_COLUMN_NAMES__ERRNO',
        RUNTIME_CENOZO_BASE_ERRNO + 21 );
define( 'RUNTIME__CENOZO_DATABASE_DATABASE__GET_COLUMN_TYPE__ERRNO',
        RUNTIME_CENOZO_BASE_ERRNO + 22 );
define( 'RUNTIME__CENOZO_DATABASE_DATABASE__GET_COLUMN_DATA_TYPE__ERRNO',
        RUNTIME_CENOZO_BASE_ERRNO + 23 );
define( 'RUNTIME__CENOZO_DATABASE_DATABASE__GET_COLUMN_KEY__ERRNO',
        RUNTIME_CENOZO_BASE_ERRNO + 24 );
define( 'RUNTIME__CENOZO_DATABASE_DATABASE__GET_COLUMN_DETAILS__ERRNO',
        RUNTIME_CENOZO_BASE_ERRNO + 25 );
define( 'RUNTIME__CENOZO_DATABASE_DATABASE__GET_COLUMN_DEFAULT__ERRNO',
        RUNTIME_CENOZO_BASE_ERRNO + 26 );
define( 'RUNTIME__CENOZO_DATABASE_DATABASE__GET_UNIQUE_KEYS__ERRNO',
        RUNTIME_CENOZO_BASE_ERRNO + 27 );
define( 'RUNTIME__CENOZO_DATABASE_DATABASE__GET_PRIMARY_KEY__ERRNO',
        RUNTIME_CENOZO_BASE_ERRNO + 28 );
define( 'RUNTIME__CENOZO_DATABASE_LIMESURVEY_RECORD____CALL__ERRNO',
        RUNTIME_CENOZO_BASE_ERRNO + 29 );
define( 'RUNTIME__CENOZO_DATABASE_LIMESURVEY_SID_RECORD__GET_TABLE_NAME__ERRNO',
        RUNTIME_CENOZO_BASE_ERRNO + 30 );
define( 'RUNTIME__CENOZO_DATABASE_LIMESURVEY_SURVEY__GET_RESPONSE__ERRNO',
        RUNTIME_CENOZO_BASE_ERRNO + 31 );
define( 'RUNTIME__CENOZO_DATABASE_LIMESURVEY_SURVEY__GET_RESPONSES__ERRNO',
        RUNTIME_CENOZO_BASE_ERRNO + 32 );
define( 'RUNTIME__CENOZO_DATABASE_LIMESURVEY_SURVEY_TIMINGS__GET_TABLE_NAME__ERRNO',
        RUNTIME_CENOZO_BASE_ERRNO + 33 );
define( 'RUNTIME__CENOZO_DATABASE_LIMESURVEY_SURVEYS__GET_TOKEN_ATTRIBUTE_NAMES__ERRNO',
        RUNTIME_CENOZO_BASE_ERRNO + 34 );
define( 'RUNTIME__CENOZO_DATABASE_MODIFIER__FROM_JSON__ERRNO',
        RUNTIME_CENOZO_BASE_ERRNO + 35 );
define( 'RUNTIME__CENOZO_DATABASE_PARTICIPANT__SET_PREFERRED_SITE__ERRNO',
        RUNTIME_CENOZO_BASE_ERRNO + 36 );
define( 'RUNTIME__CENOZO_DATABASE_PARTICIPANT__MULTI_SET_PREFERRED_SITE__ERRNO',
        RUNTIME_CENOZO_BASE_ERRNO + 37 );
define( 'RUNTIME__CENOZO_DATABASE_RECORD____CONSTRUCT__ERRNO',
        RUNTIME_CENOZO_BASE_ERRNO + 38 );
define( 'RUNTIME__CENOZO_DATABASE_RECORD__LOAD__ERRNO',
        RUNTIME_CENOZO_BASE_ERRNO + 39 );
define( 'RUNTIME__CENOZO_DATABASE_RECORD____SET__ERRNO',
        RUNTIME_CENOZO_BASE_ERRNO + 40 );
define( 'RUNTIME__CENOZO_DATABASE_RECORD____CALL__ERRNO',
        RUNTIME_CENOZO_BASE_ERRNO + 41 );
define( 'RUNTIME__CENOZO_DATABASE_SELECT__GET_SQL__ERRNO',
        RUNTIME_CENOZO_BASE_ERRNO + 42 );
define( 'RUNTIME__CENOZO_DATABASE_SELECT__FROM_JSON__ERRNO',
        RUNTIME_CENOZO_BASE_ERRNO + 43 );
define( 'RUNTIME__CENOZO_FACTORY__SELF__ERRNO',
        RUNTIME_CENOZO_BASE_ERRNO + 44 );
define( 'RUNTIME__CENOZO_FACTORY__EXISTS__ERRNO',
        RUNTIME_CENOZO_BASE_ERRNO + 45 );
define( 'RUNTIME__CENOZO_LIB__AUTOLOAD__ERRNO',
        RUNTIME_CENOZO_BASE_ERRNO + 46 );
define( 'RUNTIME__CENOZO_LIB__GET_CLASS_NAME__ERRNO',
        RUNTIME_CENOZO_BASE_ERRNO + 47 );
define( 'RUNTIME__CENOZO_LOG__INITIALIZE_LOGGER__ERRNO',
        RUNTIME_CENOZO_BASE_ERRNO + 48 );
define( 'RUNTIME__CENOZO_SERVICE_QUERY__PREPARE__ERRNO',
        RUNTIME_CENOZO_BASE_ERRNO + 49 );
define( 'RUNTIME__CENOZO_SERVICE_SELF_PATCH__EXECUTE__ERRNO',
        RUNTIME_CENOZO_BASE_ERRNO + 50 );
define( 'RUNTIME__CENOZO_SERVICE_SERVICE__VALIDATE__ERRNO',
        RUNTIME_CENOZO_BASE_ERRNO + 51 );
define( 'RUNTIME__CENOZO_SERVICE_SERVICE__GET_RECORD_CLASS_NAME__ERRNO',
        RUNTIME_CENOZO_BASE_ERRNO + 52 );

/**
 * "system" error codes
 * 
 * Since system errors already have codes this list is likely to stay empty.
 * Note the following PHP error codes:
 *      1: error,
 *      2: warning,
 *      4: parse,
 *      8: notice,
 *     16: core error,
 *     32: core warning,
 *     64: compile error,
 *    128: compile warning,
 *    256: user error,
 *    512: user warning,
 *   1024: user notice
 */

/**
 * "template" error codes
 * 
 * Since template errors already have codes this list is likely to stay empty.
 */

