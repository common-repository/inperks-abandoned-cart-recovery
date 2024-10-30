<?php
defined('ABSPATH') || exit;

class Acbwm_Lib_Template_Parser
{
    /**
     * Parse the template
     * @param $template
     * @param $data
     * @return false|string
     */
    public static function parse($template, $data)
    {
        $template = self::parse_conditions($template);
        $template = self::parse_loop($template);
        $template = self::parse_variables($template);
        ob_start();
        if (is_object($data)) {
            $encoded = wp_json_encode($data);
            $data = json_decode($encoded, true);
        }
        extract($data);
        eval('?>' . $template . '<?php ');
        return ob_get_clean();
    }

    /**
     * parse the variable
     * @param $template
     * @return string|string[]|null
     */
    public static function parse_variables($template)
    {
        $template = preg_replace('~\{(\w+)\}~', '<?php echo $$1; ?>', $template);
        return $template;
    }

    /**
     * Parse the for loop
     * @param $template
     * @return string|string[]|null
     */
    public static function parse_loop($template)
    {
        $template = preg_replace('~\{for:(\w+)\}~', '<?php foreach ($$1 as $$1_key=>$$1_value): if(is_array($$1_value)){extract($$1_value);} ?>', $template);
        $template = preg_replace('~\{endfor\}~', '<?php endforeach; ?>', $template);
        return $template;
    }

    /**
     * Parse conditional statements
     * @param $template
     * @return false|string
     */
    public static function parse_conditions($template)
    {
        $pattern = '/\{\s*(if|elseif)\s*((?:\()?(.*?)(?:\))?)\s*\}/ms';
        /**
         * For each match:
         * [0] = raw match `{if var}`
         * [1] = conditional `if`
         * [2] = condition `do === true`
         * [3] = same as [2]
         */
        preg_match_all($pattern, $template, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            // Build the string to replace the `if` statement with.
            $condition = $match[2];
            $statement = $match[1] === 'elseif' ? '<?php elseif (' . $condition . '): ?>' : '<?php if (' . $condition . '): ?>';
            $template = str_replace($match[0], $statement, $template);
        }
        $template = preg_replace('/\{\s*else\s*\}/ms', '<?php else: ?>', $template);
        $template = preg_replace('/\{\s*endif\s*\}/ms', '<?php endif; ?>', $template);
        return $template;
    }
}