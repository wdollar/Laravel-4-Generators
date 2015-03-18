<?php
/**
 * Created by PhpStorm.
 * User: vlad
 * Date: 15-03-18
 * Time: 3:12 AM
 */

if (!function_exists('scopedExplode'))
{
    /**
     * @param $delimiters       array|string
     * @param $open             array|string
     * @param $close            array|string
     * @param $text             string
     * @param $limit            max # of parts, if <1 then nothing will be done
     * @param $flags            16 - want delimiters, return is [ $text, $delim ], last part has delim of '' if no delim
     *                          folowing it
     *                          32 - trim parts of whitespace chars, trim() called without $charMask so default is used.
     *                          1 - treat first delimiter as record separator, and second+ as field sep and return an array
     *                          of arrays of fields.
     *                          2 - same as above but treat the first field as record id and create an associative array
     *                          of id to array of its fields
     *                          3 - as 8 but treat the second field as type and return an array of
     *                          [firstField=>['type'=>secondField, 'options'=>[rest of fields]]
     *                          4 - same as 3 except return object with name = firstField, type=secondField, options=rest
     *                          of fields.
     *
     *                          NOTE: first the parts with seps are constructed by parsing the text then array types are
     *                          built from the parsed parts.
     *
     * @param $openScopes       an array of open scopes and unmatched singleton scopes if any. If none then this
     *                          will be null. Can use it to check for errors. Last two items in the array will be the index
     *                          of unused $text and $pos of last matched anything.
     *
     * @return array            see above
     *
     */
    define('SCOPED_EXPLODE_WANT_RECORD', 1);
    define('SCOPED_EXPLODE_WANT_ID_RECORD', 2);
    define('SCOPED_EXPLODE_WANT_ID_TYPE_OPTIONS', 3);
    define('SCOPED_EXPLODE_WANT_OBJ', 4);
    define('SCOPED_EXPLODE_DELIMS', 16);
    define('SCOPED_EXPLODE_TRIM', 32);

    function scopedExplode($delimiters, $scopes, $text, $limit = null, $flags = 0, &$openScopes = null)
    {
        $wantArray = (is_array($delimiters) && count($delimiters) >= 2) ? ($flags & 15) : 0;
        $delimParts = ($flags & SCOPED_EXPLODE_DELIMS) | $wantArray;
        $trimParts = $flags & SCOPED_EXPLODE_TRIM;

        if (is_null($limit)) $limit = 0x0fffffff;
        if ($limit <= 1 || is_array($delimiters) && empty($delimiters) || !is_array($delimiters) && $delimiters == '')
        {
            if ($delimParts)
            {
                return ['', $text];
            }
            return [$text];
        }

        $parts = [];
        $indMask = 0x0fffffff;
        $typeMask = 0xf0000000;
        $typeDelim = 0x00000000;
        $typeSingle = 0x10000000;
        $typeOpen = 0x20000000;
        $typeClose = 0x30000000;

        if (!is_array($scopes)) $scopes = [$scopes];

        $regexScopes = [];
        $regexStrings = [];
        $scopeCount = 0;
        $regex = '';

        if (is_array($delimiters))
        {
            foreach ($delimiters as $delimiter)
            {
                $regex .= '|(' . preg_quote($delimiter, '/') . ')';
                $regexScopes[$delimiter] = $scopeCount++;
                $regexStrings[] = $delimiter;
            }
        }
        else
        {
            $regex .= '|(' . preg_quote($delimiters, '/') . ')';
            $regexScopes[$delimiters] = $scopeCount++;
            $regexStrings[] = $delimiters;
        }

        $regex = '/' . substr($regex, 1);

        foreach ($scopes as $open => $close)
        {
            if (is_int($open))
            {
                // this is a singleton
                $regex .= '|(' . preg_quote($close, '/') . ')';
                $regexScopes[$close] = $typeSingle | $scopeCount++;
                $regexStrings[] = $close;
            }
            else
            {
                $regex .= '|(' . preg_quote($open, '/') . ')';
                $regexScopes[$open] = $typeOpen | $scopeCount++;
                $regexStrings[] = $open;
                $regex .= '|(' . preg_quote($close, '/') . ')';
                $regexScopes[$close] = $typeClose | $scopeCount++;
                $regexStrings[] = $close;
            }
        }
        $regex .= '/';

        $pos = 0;
        $scopeStack = [];
        $partPos = 0;
        $textLen = strlen($text);
        $openScopes = null;
        while ($limit > 1 && $pos < $textLen)
        {
            if (!preg_match($regex, $text, $matches, PREG_OFFSET_CAPTURE, $pos))
            {
                // we did what we could
                break;
            }

            $str = $matches[0][0];
            $offs = $matches[0][1];

            $type = $regexScopes[$str];
            $regexInd = $type & $indMask;
            $regexType = $type & $typeMask;

            $pos = $offs + strlen($str);
            if (empty($scopeStack))
            {
                if ($regexType === $typeDelim)
                {
                    if ($trimParts)
                    {
                        $part = trim(substr($text, $partPos, $offs - $partPos));
                    }
                    else
                    {
                        $part = substr($text, $partPos, $offs - $partPos);
                    }

                    if ($delimParts)
                    {
                        $parts[] = [$part, $str];
                    }
                    else
                    {
                        $parts[] = $part;
                    }
                    $partPos = $pos;
                    $limit--;
                }
            }
            // we ignore all but opening, matching closing and singletons
            if ($regexType === $typeClose)
            {
                if (!empty($scopeStack))
                {
                    if ($scopeStack[0][0] === ($regexInd | $typeClose))
                    {
                        // found our closing
                        array_shift($scopeStack);
                    }
                }
                else
                {
                    // TODO: inform the caller that there is an close context without an open
                    throw new \Exception("unexpected close scope $str at $pos '" . substr($text, $pos > 32 ? $pos - 32 : 0, 64) . "'");
                }
            }
            elseif ($regexType === $typeOpen)
            {
                array_unshift($scopeStack, [$typeClose | ($regexInd + 1), $pos]);
            }
            elseif ($regexType === $typeSingle)
            {
                // look for its match
                $offs = strpos($text, $str, $pos);
                if ($offs === false)
                {
                    // unmatched singleton
                    array_unshift($scopeStack, [$typeSingle | ($regexInd), $pos]);
                    break;
                }
            }
        }

        if ($partPos < $textLen)
        {
            if ($trimParts)
            {
                $part = trim(substr($text, $partPos));
            }
            else
            {
                $part = substr($text, $partPos);
            }
            if ($delimParts)
            {
                $parts[] = [$part, ''];
            }
            else
            {
                $parts[] = $part;
            }
        }

        if (!empty($scopeStack))
        {
            $openScopes = [];
            $lastPos = $scopeStack[0][1];

            foreach ($scopeStack as $type)
            {
                $regexInd = $type & $indMask;
                $regexType = $type & $typeMask;
                $str = $regexStrings[$regexInd - ($regexType === $typeClose)];
                array_unshift($openScopes, $str);
            }
            $openScopes[] = substr(substr($text, 0, $lastPos), -32);
            $openScopes[] = substr($text, $lastPos, 32);
            $openScopes[] = $partPos;
            $openScopes[] = $pos;
        }

        if ($wantArray)
        {
            $records = [];
            $record = [];

            $recSep = $regexStrings[0];

            foreach ($parts as $part)
            {
                $record[] = $part[0];
                if ($recSep === $part[1])
                {
                    $records[] = $record;
                    $record = [];
                }
            }

            if (!empty($record))
            {
                $records[] = $record;
            }

            switch ($wantArray)
            {
                case SCOPED_EXPLODE_WANT_ID_RECORD:
                    // first is id to point to the rest
                    $parts = [];
                    foreach ($records as &$record)
                    {
                        $id = array_shift($record);
                        $parts[$id] = $record;
                    }
                    break;

                case SCOPED_EXPLODE_WANT_ID_TYPE_OPTIONS:
                    // first is id to point to ['type'=>fieldTwo, 'options'=> the rest
                    $parts = [];
                    foreach ($records as &$record)
                    {
                        $id = array_shift($record);
                        $type = array_shift($record);
                        $parts[$id] = ['type' => $type, 'options' => $record];
                    }
                    break;

                case SCOPED_EXPLODE_WANT_OBJ:
                    // same as above except stdobject and id is in name
                    $parts = [];
                    foreach ($records as &$record)
                    {
                        $obj = new \stdClass();
                        $obj->name = array_shift($record);
                        $obj->type = array_shift($record);
                        $obj->options = $record;
                        $parts[] = $obj;
                    }
                    break;

                case SCOPED_EXPLODE_WANT_RECORD:
                default:
                    // all done
                    $parts = $records;
                    break;
            }
        }

        return $parts;
    }
}

if (!function_exists('hasIt'))
{
    /**
     * @param      $haystack
     * @param      $needles
     *
     * @param bool $want        1 -  want all needles to be in the haystack
     *                          2 -  want needles to be prefixes of haystack
     *                          or the two to get both
     *                          4 - return the first value found, used with want prefix and not want all
     *
     * @return bool|int         if a single value is passed then returns true/false, if $needles is an array then
     *                          returns number of needles found in the haystack.
     */

    define('HASIT_WANT_ALL',1);
    define('HASIT_WANT_PREFIX',2);
    define('HASIT_WANT_VALUE', 4);

    function hasIt($haystack, $needles, $want = 0)
    {
        $wantAll = $want & HASIT_WANT_ALL;
        $wantPrefix = $want & HASIT_WANT_PREFIX;
        $wantValue = $want & HASIT_WANT_VALUE;

        if (!is_array($needles)) $needles = [$needles];
        $has = 0;
        $cnt = 0;

        if ($wantPrefix)
        {
            foreach ($needles as $needle)
            {
                $cnt++;
                foreach ($haystack as $value)
                {
                    if (strpos($value, $needle) === 0)
                    {
                        if (!$wantAll) return $wantValue ? $value : true;
                        $has++;
                    }
                    else
                    {
                        if ($wantAll) return false;
                    }
                }
            }
        }
        else
        {
            foreach ($needles as $needle)
            {
                $cnt++;
                foreach ($haystack as $value)
                {
                    if ($value === $needle)
                    {
                        if (!$wantAll) return $wantValue ? $value : true;
                        $has++;
                    }
                    else
                    {
                        if ($wantAll) return false;
                    }
                }
            }
        }
        return $has;
    }
}

