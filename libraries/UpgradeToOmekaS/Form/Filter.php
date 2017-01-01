<?php

class UpgradeToOmekaS_Form_Filter extends Zend_Filter_Callback
{
    /**
     * Callback to get real path of a string even if the path doesn't exist yet.
     *
     * @link https://stackoverflow.com/questions/21421569/sanitize-file-path-in-php-without-realpath#21424232
     *
     * @param string $value The value to filter.
     * @return string
     */
    static public function filterRemoveDotSegments($value)
    {
        // as per RFC 3986
        // @see http://tools.ietf.org/html/rfc3986#section-5.2.4

        $input = $value;
        // 1.  The input buffer is initialized with the now-appended path
        //     components and the output buffer is initialized to the empty
        //     string.
        $output = '';

        // 2.  While the input buffer is not empty, loop as follows:
        while ($input !== '') {
            // A.  If the input buffer begins with a prefix of "`../`" or "`./`",
            //     then remove that prefix from the input buffer; otherwise,
            if (
                    ($prefix = substr($input, 0, 3)) == '../'
                    || ($prefix = substr($input, 0, 2)) == './'
                ) {
                $input = substr($input, strlen($prefix));
            } else

                // B.  if the input buffer begins with a prefix of "`/./`" or "`/.`",
                //     where "`.`" is a complete path segment, then replace that
                //     prefix with "`/`" in the input buffer; otherwise,
                if (
                        ($prefix = substr($input, 0, 3)) == '/./'
                        || ($prefix = $input) == '/.'
                    ) {
                    $input = '/' . substr($input, strlen($prefix));
                } else

                    // C.  if the input buffer begins with a prefix of "/../" or "/..",
                    //     where "`..`" is a complete path segment, then replace that
                    //     prefix with "`/`" in the input buffer and remove the last
                    //     segment and its preceding "/" (if any) from the output
                    //     buffer; otherwise,
                    if (
                            ($prefix = substr($input, 0, 4)) == '/../'
                            || ($prefix = $input) == '/..'
                        ) {
                        $input = '/' . substr($input, strlen($prefix));
                        $output = substr($output, 0, strrpos($output, '/'));
                        } else

                        // D.  if the input buffer consists only of "." or "..", then remove
                        //     that from the input buffer; otherwise,
                        if ($input == '.' || $input == '..') {
                            $input = '';
                        } else

                            // E.  move the first path segment in the input buffer to the end of
                            //     the output buffer, including the initial "/" character (if
                            //     any) and any subsequent characters up to, but not including,
                            //     the next "/" character or the end of the input buffer.
                        {
                            $pos = strpos($input, '/');
                            if ($pos === 0) $pos = strpos($input, '/', $pos+1);
                            if ($pos === false) $pos = strlen($input);
                            $output .= substr($input, 0, $pos);
                            $input = (string) substr($input, $pos);
                        }
        }

        // 3.  Finally, the output buffer is returned as the result of remove_dot_segments.
        return $output;
    }
}
