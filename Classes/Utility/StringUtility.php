<?php
namespace In2code\Migration\Utility;

/**
 * Class StringUtility
 */
class StringUtility
{

    /**
     * Parse every kind of Youtube URI and return video code
     *
     *  Example URI:
     *      http://www.youtube.com/embed/6FjfewWAGdE?feature=player_detailpage
     *      http://www.youtube.com/embed/6FjfewWAGdE?feature=player_detailpage
     *      https://www.youtube.com/embed/6FjfewWAGdE
     *      https://youtube.com/embed/6FjfewWAGdE
     *      http://www.youtube.com/watch?v=6FjfewWAGdE
     *      http://www.youtube.com/watch?v=6FjfewWAGdE&feature=player_detailpage
     *      www.youtu.be/6FjfewWAGdE
     *      youtu.be/6FjfewWAGdE
     *      youtube.com/watch?v=6FjfewWAGdE
     *      https://www.youtube.com/watch?v=6FjfewWAGdE&feature=youtu.be
     *
     * @param string $uri
     * @return string
     */
    public static function getYoutubeCodeFromUri(string $uri): string
    {
        $code = '';
        $regExp = '~^(http://|https://|.*?)(www.|.*?)(youtube.com|youtu.be)/(embed/|watch\?v=|.*?)(.*?)(\?|\&|$)~';
        preg_match($regExp, $uri, $result);
        if (!empty($result[5])) {
            $code = $result[5];
        }
        return $code;
    }

    /**
     * Replace a css class with another in a html string
     *
     *  Testcases:
     *      <a href="/test" class="abc">abc</a>
     *      <a href="/test" class="abc abc">test</a>
     *      <a href="/test" class="ll abc">test</a>
     *      <a href="/test" class="abc ll_t-a">test</a>
     *      <a href="abc" class="ll abc kk">test</a>
     *      <a href="/abc" class="l__k abc l-l" />
     *      <div class="l__l abc k-k" abc="abc" />
     *      <div data="abc" class="abc abc1 abc a2bc" abc="abc" />
     *      <div data=" abc" class="l__l abc k-k" abc="abc" />
     *
     * @param string $search
     * @param string $replace
     * @param string $string
     * @return string
     */
    public static function replaceCssClassInHtmlString(string $search, string $replace, string $string): string
    {
        $expression = '~(class="[^"]*)\b' . preg_quote($search) . '\b([^"]*)~U';
        return preg_replace($expression, '${1}' . $replace . '$2', $string);
    }

    /**
     * Replace a css class in a string with more classes which are separated with a space
     * E.g. Replace "foo" with "new" in "foo foo-bar bar" - Result: "new foo-bar bar"
     *
     * @param string $search
     * @param string $replace
     * @param string $string
     * @return string
     */
    public static function replaceCssClassInString(string $search, string $replace, string $string): string
    {
        $classes = explode(' ', $string);
        foreach ($classes as $key => $class) {
            if ($class === $search) {
                $classes[$key] = $replace;
            }
        }
        return implode(' ', $classes);
    }

    /**
     * Check if string starts with another string
     *
     * @param string $haystack
     * @param string $needle
     * @return bool
     */
    public static function startsWith(string $haystack, string $needle): bool
    {
        return stristr($haystack, $needle) && strrpos($haystack, $needle, -strlen($haystack)) !== false;
    }
}
