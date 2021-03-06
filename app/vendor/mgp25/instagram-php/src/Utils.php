<?php

namespace InstagramAPI;

use InstagramAPI\Request\Metadata\MediaDetails;
use InstagramAPI\Request\Metadata\PhotoDetails;
use InstagramAPI\Request\Metadata\VideoDetails;
use InstagramAPI\Response\Model\Item;

class Utils
{
    /**
     * Override for the default temp path used by various class functions.
     *
     * If this value is non-null, we'll use it. Otherwise we'll use the default
     * system tmp folder.
     *
     * TIP: If your default system temp folder isn't writable, it's NECESSARY
     * for you to set this value to another, writable path, like this:
     *
     * \InstagramAPI\Utils::$defaultTmpPath = '/home/example/foo/';
     */
    public static $defaultTmpPath = null;

    /**
     * Used for multipart boundary generation.
     *
     * @var string
     */
    const BOUNDARY_CHARS = '-_1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    /**
     * Length of generated multipart boundary.
     *
     * @var int
     */
    const BOUNDARY_LENGTH = 30;

    /**
     * Name of the detected ffmpeg executable, or FALSE if none found.
     *
     * @var string|bool|null
     */
    public static $ffmpegBin = null;

    /**
     * Name of the detected ffprobe executable, or FALSE if none found.
     *
     * @var string|bool|null
     */
    public static $ffprobeBin = null;

    /**
     * Last uploadId generated with microtime().
     *
     * @var string|null
     */
    protected static $_lastUploadId = null;

    /**
     * @param bool $useNano Whether to return result in usec instead of msec.
     *
     * @return string
     */
    public static function generateUploadId(
        $useNano = false)
    {
        $result = null;
        if (!$useNano) {
            while (true) {
                $result = number_format(round(microtime(true) * 1000), 0, '', '');
                if (self::$_lastUploadId !== null && $result === self::$_lastUploadId) {
                    // NOTE: Fast machines can process files too quick (< 0.001
                    // sec), which leads to identical upload IDs, which leads to
                    // "500 Oops, an error occurred" errors. So we sleep 0.001
                    // sec to guarantee different upload IDs per each call.
                    usleep(1000);
                } else { // OK!
                    self::$_lastUploadId = $result;
                    break;
                }
            }
        } else {
            // Emulate System.nanoTime().
            $result = number_format(microtime(true) - strtotime('Last Monday'), 6, '', '');
            // Append nanoseconds.
            $result .= str_pad((string) mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
        }

        return $result;
    }

    /**
     * Calculates Java hashCode() for a given string.
     *
     * WARNING: This method is not Unicode-aware, so use it only on ANSI strings.
     *
     * @param string $string
     *
     * @return int
     *
     * @see https://en.wikipedia.org/wiki/Java_hashCode()#The_java.lang.String_hash_function
     */
    public static function hashCode(
        $string)
    {
        $result = 0;
        for ($i = 0, $len = strlen($string); $i < $len; $i++) {
            $result = (-$result + ($result << 5) + ord($string[$i])) & 0xFFFFFFFF;
        }
        if (PHP_INT_SIZE > 4) {
            if ($result > 0x7FFFFFFF) {
                $result -= 0x100000000;
            } elseif ($result < -0x80000000) {
                $result += 0x100000000;
            }
        }

        return $result;
    }

    /**
     * Reorders array by hashCode() of its keys.
     *
     * @param array $data
     *
     * @return array
     */
    public static function reorderByHashCode(
        array $data)
    {
        $hashCodes = [];
        foreach ($data as $key => $value) {
            $hashCodes[$key] = self::hashCode($key);
        }

        uksort($data, function ($a, $b) use ($hashCodes) {
            $a = $hashCodes[$a];
            $b = $hashCodes[$b];
            if ($a < $b) {
                return -1;
            } elseif ($a > $b) {
                return 1;
            } else {
                return 0;
            }
        });

        return $data;
    }

    /**
     * Generates random multipart boundary string.
     *
     * @return string
     */
    public static function generateMultipartBoundary()
    {
        $result = '';
        $max = strlen(self::BOUNDARY_CHARS) - 1;
        for ($i = 0; $i < self::BOUNDARY_LENGTH; ++$i) {
            $result .= self::BOUNDARY_CHARS[mt_rand(0, $max)];
        }

        return $result;
    }

    /**
     * Generates user breadcrumb for use when posting a comment.
     *
     * @param int $size
     *
     * @return string
     */
    public static function generateUserBreadcrumb(
        $size)
    {
        $key = 'iN4$aGr0m';
        $date = (int) (microtime(true) * 1000);

        // typing time
        $term = rand(2, 3) * 1000 + $size * rand(15, 20) * 100;

        // android EditText change event occur count
        $text_change_event_count = round($size / rand(2, 3));
        if ($text_change_event_count == 0) {
            $text_change_event_count = 1;
        }

        // generate typing data
        $data = $size.' '.$term.' '.$text_change_event_count.' '.$date;

        return base64_encode(hash_hmac('sha256', $data, $key, true))."\n".base64_encode($data)."\n";
    }

    /**
     * Check for ffmpeg/avconv dependencies.
     *
     * TIP: If your binary isn't findable via the PATH environment locations,
     * you can manually set the correct path to it. Before calling any functions
     * that need FFmpeg, you must simply assign a manual value (ONCE) to tell us
     * where to find your FFmpeg, like this:
     *
     * \InstagramAPI\Utils::$ffmpegBin = '/home/exampleuser/ffmpeg/bin/ffmpeg';
     *
     * @return string|bool Name of the library if present, otherwise FALSE.
     */
    public static function checkFFMPEG()
    {
        // We only resolve this once per session and then cache the result.
        if (self::$ffmpegBin === null) {
            @exec('ffmpeg -version 2>&1', $output, $statusCode);
            if ($statusCode === 0) {
                self::$ffmpegBin = 'ffmpeg';
            } else {
                @exec('avconv -version 2>&1', $output, $statusCode);
                if ($statusCode === 0) {
                    self::$ffmpegBin = 'avconv';
                } else {
                    self::$ffmpegBin = false; // Nothing found!
                }
            }
        }

        return self::$ffmpegBin;
    }

    /**
     * Check for ffprobe dependency.
     *
     * TIP: If your binary isn't findable via the PATH environment locations,
     * you can manually set the correct path to it. Before calling any functions
     * that need FFprobe, you must simply assign a manual value (ONCE) to tell
     * us where to find your FFprobe, like this:
     *
     * \InstagramAPI\Utils::$ffprobeBin = '/home/exampleuser/ffmpeg/bin/ffprobe';
     *
     * @return string|bool Name of the library if present, otherwise FALSE.
     */
    public static function checkFFPROBE()
    {
        // We only resolve this once per session and then cache the result.
        if (self::$ffprobeBin === null) {
            @exec('ffprobe -version 2>&1', $output, $statusCode);
            if ($statusCode === 0) {
                self::$ffprobeBin = 'ffprobe';
            } else {
                self::$ffprobeBin = false; // Nothing found!
            }
        }

        return self::$ffprobeBin;
    }

    /**
     * Get detailed information about a photo file.
     *
     * @param string $photoFilename Path to the photo file.
     *
     * @throws \InvalidArgumentException If the photo file is missing or invalid.
     *
     * @return array Int file size, image type, width and height.
     */
    public static function getPhotoFileDetails(
        $photoFilename)
    {
        // Check if input file exists.
        if (empty($photoFilename) || !is_file($photoFilename)) {
            throw new \InvalidArgumentException(__('The photo file "%s" does not exist on disk.', $photoFilename));
        }

        // Determine photo file size and throw when the file is empty.
        $filesize = filesize($photoFilename);
        if ($filesize < 1) {
            throw new \InvalidArgumentException(__(
                'The photo file "%s" is empty.',
                $photoFilename
            ));
        }

        // Get image details.
        $result = @getimagesize($photoFilename);
        if ($result === false) {
            throw new \InvalidArgumentException(__('The photo file "%s" is not a valid image.', $photoFilename));
        }

        return [
            'filesize' => $filesize,
            'width'    => $result[0],
            'height'   => $result[1],
            'type'     => $result[2],
        ];
    }

    /**
     * Get detailed information about a video file.
     *
     * This also validates that a file is actually a video, since FFmpeg will
     * fail to read details from badly broken / non-video files.
     *
     * @param string $videoFilename Path to the video file.
     *
     * @throws \InvalidArgumentException If the video file is missing.
     * @throws \RuntimeException         If FFmpeg isn't working properly.
     *
     * @return array Video codec name, float duration, int width, height and filesize.
     */
    public static function getVideoFileDetails(
        $videoFilename)
    {
        // Determine video file size and throw when the file is empty.
        $filesize = filesize($videoFilename);
        if ($filesize < 1) {
            throw new \InvalidArgumentException(__(
                'The video "%s" is empty.',
                $videoFilename
            ));
        }

        // The user must have FFprobe.
        $ffprobe = self::checkFFPROBE();
        if ($ffprobe === false) {
            throw new \RuntimeException(__('You must have FFprobe to analyze video details.'));
        }

        // Check if input file exists.
        if (empty($videoFilename) || !is_file($videoFilename)) {
            throw new \InvalidArgumentException(__('The video file "%s" does not exist on disk.', $videoFilename));
        }

        // Load with FFPROBE. Shows details as JSON and exits.
        $command = escapeshellarg($ffprobe).' -v quiet -print_format json -show_format -show_streams '.escapeshellarg($videoFilename);
        $jsonInfo = @shell_exec($command);

        // Check for processing errors.
        if ($jsonInfo === null) {
            throw new \RuntimeException(__('FFprobe failed to analyze your video file "%s".', $videoFilename));
        }

        // Attempt to decode the JSON.
        $probeResult = @json_decode($jsonInfo, true);
        if ($probeResult === null) {
            throw new \RuntimeException(__('FFprobe gave us invalid JSON for "%s".', $videoFilename));
        }

        // Now analyze all streams to find the first video stream.
        // We ignore all audio and subtitle streams.
        $videoDetails = [];
        foreach ($probeResult['streams'] as $streamIdx => $streamInfo) {
            if ($streamInfo['codec_type'] == 'video') {
                $videoDetails['filesize'] = $filesize;
                $videoDetails['codec'] = $streamInfo['codec_name']; // string
                $videoDetails['width'] = intval($streamInfo['width'], 10);
                $videoDetails['height'] = intval($streamInfo['height'], 10);
                // NOTE: Duration is a float such as "230.138000".
                $videoDetails['duration'] = floatval($streamInfo['duration']);

                break; // Stop checking streams.
            }
        }

        // Make sure we have found format details.
        if (count($videoDetails) === 0) {
            throw new \RuntimeException(__('FFprobe failed to detect any video format details. Is "%s" a valid video file?', $videoFilename));
        }

        return $videoDetails;
    }

    /**
     * Verifies that a piece of media follows Instagram's size/aspect rules.
     *
     * We bring in the up-to-date rules from the MediaAutoResizer class.
     *
     * @param int          $targetFeed   One of the FEED_X constants.
     * @param MediaDetails $mediaDetails Media details.
     *
     * @throws \InvalidArgumentException If Instagram won't allow this file.
     *
     * @see MediaAutoResizer
     */
    public static function throwIfIllegalMediaResolution(
        $targetFeed,
        MediaDetails $mediaDetails)
    {
        $width = $mediaDetails->getWidth();
        $height = $mediaDetails->getHeight();

        // WARNING TO CONTRIBUTORS: $mediaFilename is for ERROR DISPLAY to
        // users. Do NOT use it to read from the hard disk!
        $mediaFilename = $mediaDetails->getFilename();

        // Check Media Width.
        // NOTE: They have height-limits too, but we automatically enforce
        // those when validating the aspect ratio range further down.
        if ($mediaDetails instanceof PhotoDetails) {
            // Validate photo resolution. Instagram allows between 320px-1080px width.
            if ($width < 320 || $width > MediaAutoResizer::MAX_WIDTH) {
                throw new \InvalidArgumentException(__(
                    'Instagram only accepts photos that are between 320 and %d pixels wide. Your file "%s" is %d pixels wide.',
                    MediaAutoResizer::MAX_WIDTH, $mediaFilename, $width
                ));
            }
        } elseif ($mediaDetails instanceof VideoDetails) {
            // Validate video resolution. Instagram allows between 480px-720px width.
            // NOTE: They'll resize 720px wide videos on the server, to 640px instead.
            // NOTE: Their server CAN receive between 320px-1080px width without
            // rejecting the file, but the official app would NEVER upload such
            // resolutions. It's controlled by the "ig_android_universe_video_production"
            // experiment variable, which currently enforces width of min:480, max:720.
            // If users want to upload bigger videos, they MUST resize locally first!
            if ($width < 480 || $width > 720) {
                throw new \InvalidArgumentException(__(
                    'Instagram only accepts videos that are between 480 and 720 pixels wide. Your file "%s" is %d pixels wide.',
                    $mediaFilename, $width
                ));
            }
        }

        // Check Aspect Ratio.
        // NOTE: This Instagram rule is the same for both videos and photos.
        // See MediaAutoResizer for the latest up-to-date allowed ratios.
        $aspectRatio = $width / $height;
        switch ($targetFeed) {
        case Constants::FEED_STORY:
        case Constants::FEED_DIRECT_STORY:
            if ($aspectRatio < MediaAutoResizer::MIN_STORY_RATIO || $aspectRatio > MediaAutoResizer::MAX_STORY_RATIO) {
                throw new \InvalidArgumentException(__(
                    'Instagram only accepts story media with aspect ratios between %.3f and %.3f. Your file "%s" has a %.4f aspect ratio.',
                    MediaAutoResizer::MIN_STORY_RATIO, MediaAutoResizer::MAX_STORY_RATIO, $mediaFilename, $aspectRatio
                ));
            }
            break;
        default:
            if ($aspectRatio < MediaAutoResizer::MIN_RATIO || $aspectRatio > MediaAutoResizer::MAX_RATIO) {
                throw new \InvalidArgumentException(__(
                    'Instagram only accepts media with aspect ratios between %.3f and %.3f. Your file "%s" has a %.4f aspect ratio.',
                    MediaAutoResizer::MIN_RATIO, MediaAutoResizer::MAX_RATIO, $mediaFilename, $aspectRatio
                ));
            }
        }
    }

    /**
     * Verifies that a video's details follow Instagram's requirements.
     *
     * @param int          $targetFeed   One of the FEED_X constants.
     * @param VideoDetails $videoDetails Video details.
     *
     * @throws \InvalidArgumentException If Instagram won't allow this video.
     */
    public static function throwIfIllegalVideoDetails(
        $targetFeed,
        VideoDetails $videoDetails)
    {
        $videoFilename = $videoDetails->getFilename();
        // Validate video length.
        // NOTE: Instagram has no disk size limit, but this length validation
        // also ensures we can only upload small files exactly as intended.
        $duration = $videoDetails->getDuration();
        switch ($targetFeed) {
        case Constants::FEED_STORY:
            // Instagram only allows 3-15 seconds for stories.
            if ($duration < 3 || $duration > 15) {
                throw new \InvalidArgumentException(__(
                    'Instagram only accepts story videos that are between 3 and 15 seconds long. Your story video "%s" is %.3f seconds long.',
                    $videoFilename, $duration
                ));
            }
            break;
        case Constants::FEED_DIRECT:
        case Constants::FEED_DIRECT_STORY:
            // Instagram only allows 0.1-15 seconds for direct messages.
            if ($duration < 0.1 || $duration > 15) {
                throw new \InvalidArgumentException(__(
                    'Instagram only accepts direct videos that are between 0.1 and 15 seconds long. Your direct video "%s" is %.3f seconds long.',
                    $videoFilename, $duration
                ));
            }
            break;
        default: // timeline
            // Validate video length. Instagram only allows 3-60 seconds.
            // SEE: https://help.instagram.com/270963803047681
            if ($duration < 3 || $duration > 60) {
                throw new \InvalidArgumentException(__(
                    'Instagram only accepts videos that are between 3 and 60 seconds long. Your video "%s" is %.3f seconds long.',
                    $videoFilename, $duration
                ));
            }
        }

        // Validate video resolution and aspect ratio.
        self::throwIfIllegalMediaResolution($targetFeed, $videoDetails);
    }

    /**
     * Verifies that a photo's details follow Instagram's requirements.
     *
     * @param int          $targetFeed   One of the FEED_X constants.
     * @param PhotoDetails $photoDetails Photo details.
     *
     * @throws \InvalidArgumentException If Instagram won't allow this photo.
     */
    public static function throwIfIllegalPhotoDetails(
        $targetFeed,
        PhotoDetails $photoDetails)
    {
        $photoFilename = $photoDetails->getFilename();
        // Validate image type.
        // NOTE: It is confirmed that Instagram only accepts JPEG files.
        $type = $photoDetails->getType();
        if ($type !== IMAGETYPE_JPEG) {
            throw new \InvalidArgumentException(__('The photo file "%s" is not a JPEG file.', $photoFilename));
        }

        // Validate photo resolution and aspect ratio.
        self::throwIfIllegalMediaResolution($targetFeed, $photoDetails);
    }

    /**
     * Generate a video icon/thumbnail from a video file.
     *
     * Automatically guarantees that the generated image follows Instagram's
     * allowed image specifications, so that there won't be any upload issues.
     *
     * @param int    $targetFeed    One of the FEED_X constants.
     * @param string $videoFilename Path to the video file.
     *
     * @throws \InvalidArgumentException If the video file is missing.
     * @throws \RuntimeException         If FFmpeg isn't working properly, or
     *                                   thumbnail MediaAutoResizer failed.
     * @throws \Exception                If MediaAutoResizer failed.
     *
     * @return string The JPEG binary data for the generated thumbnail.
     */
    public static function createVideoIcon(
        $targetFeed,
        $videoFilename)
    {
        // The user must have FFmpeg.
        $ffmpeg = self::checkFFMPEG();
        if ($ffmpeg === false) {
            throw new \RuntimeException(__('You must have FFmpeg to generate video thumbnails.'));
        }

        // Check if input file exists.
        if (empty($videoFilename) || !is_file($videoFilename)) {
            throw new \InvalidArgumentException(__('The video file "%s" does not exist on disk.', $videoFilename));
        }

        // Generate a temp thumbnail filename and delete if file already exists.
        $tmpPath = self::$defaultTmpPath !== null
                   ? self::$defaultTmpPath
                   : sys_get_temp_dir();
        $tmpFilename = $tmpPath.'/'.md5($videoFilename).'.jpg';
        if (is_file($tmpFilename)) {
            @unlink($tmpFilename);
        }

        try {
            // Capture a video preview snapshot to that file via FFMPEG.
            $command = escapeshellarg($ffmpeg).' -i '.escapeshellarg($videoFilename).' -f mjpeg -ss 00:00:01 -vframes 1 '.escapeshellarg($tmpFilename).' 2>&1';
            @exec($command, $output, $statusCode);

            // Check for processing errors.
            if ($statusCode !== 0) {
                throw new \RuntimeException(__('FFmpeg failed to generate a video thumbnail.'));
            }

            // Automatically crop&resize the thumbnail to Instagram's requirements.
            $resizer = new MediaAutoResizer($tmpFilename, ['targetFeed' => $targetFeed]);
            $jpegContents = file_get_contents($resizer->getFile()); // Process&get.
            $resizer->deleteFile();

            return $jpegContents;
        } finally {
            @unlink($tmpFilename);
        }
    }

    /**
     * Verifies an array of media usertags.
     *
     * Ensures that the input strictly contains the exact keys necessary for
     * usertags, and with proper values for them. We cannot validate that the
     * user-id's actually exist, but that's the job of the library user!
     *
     * @param array $usertags The array of usertags, optionally with the "in" or
     *                        "removed" top-level keys holding the usertags. Example:
     *                        ['in'=>[['position'=>[0.5,0.5],'user_id'=>'123'], ...]].
     *
     * @throws \InvalidArgumentException If any tags are invalid.
     */
    public static function throwIfInvalidUsertags(
        array $usertags)
    {
        if (count($usertags) < 1) {
            throw new \InvalidArgumentException('Empty usertags array.');
        }

        foreach ($usertags as $k => $v) {
            if ($k === 'in' || $k === 'removed') {
                if (!is_array($v)) {
                    throw new \InvalidArgumentException(__(
                        'Invalid usertags array. The value for key "%s" must be an array.', $k
                    ));
                }

                // Skip the section if it's empty.
                if (count($v) < 1) {
                    continue;
                }

                // Handle ['in'=>[...], 'removed'=>[...]] top-level keys since
                // this input contained top-level array keys containing the usertags.
                if ($k === 'in') {
                    // Check the array of usertags to insert.
                    self::throwIfInvalidUsertags($v);
                } else { // 'removed'
                    // Check the array of userids to remove.
                    foreach ($v as $userId) {
                        if (!ctype_digit($userId) && (!is_int($userId) || $userId < 0)) {
                            throw new \InvalidArgumentException(__('Invalid user ID in usertags "removed" array.'));
                        }
                    }
                }
            } else {
                // Verify this usertag entry, ensuring that the entry is format
                // ['position'=>[0.0,1.0],'user_id'=>'123'] and nothing else.
                if (!is_array($v) || count($v) != 2 || !isset($v['position'])
                    || !is_array($v['position']) || count($v['position']) != 2
                    || !isset($v['position'][0]) || !isset($v['position'][1])
                    || (!is_int($v['position'][0]) && !is_float($v['position'][0]))
                    || (!is_int($v['position'][1]) && !is_float($v['position'][1]))
                    || $v['position'][0] < 0.0 || $v['position'][0] > 1.0
                    || $v['position'][1] < 0.0 || $v['position'][1] > 1.0
                    || !isset($v['user_id']) || !is_scalar($v['user_id'])
                    || (!ctype_digit($v['user_id']) && (!is_int($v['user_id']) || $v['user_id'] < 0))) {
                    throw new \InvalidArgumentException(__('Invalid user entry in usertags array.'));
                }
            }
        }
    }

    /**
     * Verifies if a caption is valid for a hashtag and verifies an array of hashtags.
     *
     * @param string $captionText The caption for the story hashtag.
     * @param array  $hashtags    The array of usertags.
     *
     * @throws \InvalidArgumentException If caption doesn't contains any hashtag.
     *                                   or if any tags are invalid.
     */
    public static function throwIfInvalidStoryHashtags(
         $captionText,
         array $hashtags)
    {
        preg_match_all("/(#\w+)/u", $captionText, $matches);

        if (!$matches[1]) {
            throw new \InvalidArgumentException(__('Invalid caption for hashtag.'));
        }

        foreach ($hashtags as $hashtag) {
            if (!in_array($hashtag['tag_name'], $matches[1])) {
                throw new \InvalidArgumentException(__('Tag name "%s" does not exist in the caption text.', $hashtag['tag_name']));
            }
            foreach ($hashtag as $k => $v) {
                if (!in_array($k, ['tag_name', 'x', 'y', 'width', 'height', 'rotation', 'is_sticker', 'use_custom_title'], true)) {
                    throw new \InvalidArgumentException(__('Invalid key "%s" for hashtag.', $k));
                }
                if (
                     (($k !== 'is_sticker' && $k !== 'use_custom_title') && ($v < 0.0 || $v > 1.0))
                     || (($k === 'is_sticker' || $k === 'use_custom_title') && !is_bool($v))
                ) {
                    throw new \InvalidArgumentException(__('Invalid value "%s" for hashtag "%s".', $v, $k));
                }
            }
        }
    }

    /**
     * Checks and validates a media item's type.
     *
     * @param string|int $mediaType The type of the media item. One of: "PHOTO", "VIDEO"
     *                              "ALBUM", or the raw value of the Item's "getMediaType()" function.
     *
     * @throws \InvalidArgumentException If the type is invalid.
     *
     * @return string The verified final type; either "PHOTO", "VIDEO" or "ALBUM".
     */
    public static function checkMediaType(
        $mediaType)
    {
        if (ctype_digit($mediaType) || is_int($mediaType)) {
            if ($mediaType == Item::PHOTO) {
                $mediaType = 'PHOTO';
            } elseif ($mediaType == Item::VIDEO) {
                $mediaType = 'VIDEO';
            } elseif ($mediaType == Item::ALBUM) {
                $mediaType = 'ALBUM';
            }
        }
        if (!in_array($mediaType, ['PHOTO', 'VIDEO', 'ALBUM'], true)) {
            throw new \InvalidArgumentException(__('"%s" is not a valid media type.', $mediaType));
        }

        return $mediaType;
    }

    public static function formatBytes(
        $bytes,
        $precision = 2)
    {
        $units = ['B', 'kB', 'mB', 'gB', 'tB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision).''.$units[$pow];
    }

    public static function colouredString(
        $string,
        $colour)
    {
        $colours['black'] = '0;30';
        $colours['dark_gray'] = '1;30';
        $colours['blue'] = '0;34';
        $colours['light_blue'] = '1;34';
        $colours['green'] = '0;32';
        $colours['light_green'] = '1;32';
        $colours['cyan'] = '0;36';
        $colours['light_cyan'] = '1;36';
        $colours['red'] = '0;31';
        $colours['light_red'] = '1;31';
        $colours['purple'] = '0;35';
        $colours['light_purple'] = '1;35';
        $colours['brown'] = '0;33';
        $colours['yellow'] = '1;33';
        $colours['light_gray'] = '0;37';
        $colours['white'] = '1;37';

        $colored_string = '';

        if (isset($colours[$colour])) {
            $colored_string .= "\033[".$colours[$colour].'m';
        }

        $colored_string .= $string."\033[0m";

        return $colored_string;
    }

    public static function getFilterCode(
        $filter)
    {
        $filters = [];
        $filters[0] = 'Normal';
        $filters[615] = 'Lark';
        $filters[614] = 'Reyes';
        $filters[613] = 'Juno';
        $filters[612] = 'Aden';
        $filters[608] = 'Perpetua';
        $filters[603] = 'Ludwig';
        $filters[605] = 'Slumber';
        $filters[616] = 'Crema';
        $filters[24] = 'Amaro';
        $filters[17] = 'Mayfair';
        $filters[23] = 'Rise';
        $filters[26] = 'Hudson';
        $filters[25] = 'Valencia';
        $filters[1] = 'X-Pro II';
        $filters[27] = 'Sierra';
        $filters[28] = 'Willow';
        $filters[2] = 'Lo-Fi';
        $filters[3] = 'Earlybird';
        $filters[22] = 'Brannan';
        $filters[10] = 'Inkwell';
        $filters[21] = 'Hefe';
        $filters[15] = 'Nashville';
        $filters[18] = 'Sutro';
        $filters[19] = 'Toaster';
        $filters[20] = 'Walden';
        $filters[14] = '1977';
        $filters[16] = 'Kelvin';
        $filters[-2] = 'OES';
        $filters[-1] = 'YUV';
        $filters[109] = 'Stinson';
        $filters[106] = 'Vesper';
        $filters[112] = 'Clarendon';
        $filters[118] = 'Maven';
        $filters[114] = 'Gingham';
        $filters[107] = 'Ginza';
        $filters[113] = 'Skyline';
        $filters[105] = 'Dogpatch';
        $filters[115] = 'Brooklyn';
        $filters[111] = 'Moon';
        $filters[117] = 'Helena';
        $filters[116] = 'Ashby';
        $filters[108] = 'Charmes';
        $filters[640] = 'BrightContrast';
        $filters[642] = 'CrazyColor';
        $filters[643] = 'SubtleColor';

        return array_search($filter, $filters);
    }

    /**
     * Creates a folder if missing, or ensures that it is writable.
     *
     * @param string $folder The directory path.
     *
     * @return bool TRUE if folder exists and is writable, otherwise FALSE.
     */
    public static function createFolder(
        $folder)
    {
        // Test write-permissions for the folder and create/fix if necessary.
        if ((is_dir($folder) && is_writable($folder))
            || (!is_dir($folder) && mkdir($folder, 0755, true))
            || chmod($folder, 0755)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Recursively deletes a file/directory tree.
     *
     * @param string $folder         The directory path.
     * @param bool   $keepRootFolder Whether to keep the top-level folder.
     *
     * @return bool TRUE on success, otherwise FALSE.
     */
    public static function deleteTree(
        $folder,
        $keepRootFolder = false)
    {
        // Handle bad arguments.
        if (empty($folder) || !file_exists($folder)) {
            return true; // No such file/folder exists.
        } elseif (is_file($folder) || is_link($folder)) {
            return @unlink($folder); // Delete file/link.
        }

        // Delete all children.
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($folder, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            $action = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            if (!@$action($fileinfo->getRealPath())) {
                return false; // Abort due to the failure.
            }
        }

        // Delete the root folder itself?
        return !$keepRootFolder ? @rmdir($folder) : true;
    }

    /**
     * Atomic filewriter.
     *
     * Safely writes new contents to a file using an atomic two-step process.
     * If the script is killed before the write is complete, only the temporary
     * trash file will be corrupted.
     *
     * @param string $filename     Filename to write the data to.
     * @param string $data         Data to write to file.
     * @param string $atomicSuffix Lets you optionally provide a different
     *                             suffix for the temporary file.
     *
     * @return mixed Number of bytes written on success, otherwise FALSE.
     */
    public static function atomicWrite(
        $filename,
        $data,
        $atomicSuffix = 'atomictmp')
    {
        // Perform an exclusive (locked) overwrite to a temporary file.
        $filenameTmp = sprintf('%s.%s', $filename, $atomicSuffix);
        $writeResult = @file_put_contents($filenameTmp, $data, LOCK_EX);
        if ($writeResult !== false) {
            // Now move the file to its real destination (replaced if exists).
            $moveResult = @rename($filenameTmp, $filename);
            if ($moveResult === true) {
                // Successful write and move. Return number of bytes written.
                return $writeResult;
            }
        }

        return false; // Failed.
    }

    /**
     * Checks if a URL has valid "web" syntax.
     *
     * This function is Unicode-aware.
     *
     * Be aware that it only performs URL syntax validation! It doesn't check
     * if the domain/URL is fully valid and actually reachable!
     *
     * It verifies that the URL begins with either the "http://" or "https://"
     * protocol, and that it must contain a host with at least one period in it,
     * and at least two characters after the period (in other words, a TLD). The
     * rest of the string can be any sequence of non-whitespace characters.
     *
     * For example, "http://localhost" will not be seen as a valid web URL, and
     * "http://www.google.com foobar" is not a valid web URL since there's a
     * space in it. But "https://bing.com" and "https://a.com/foo" are valid.
     * However, "http://a.abdabdbadbadbsa" is also seen as a valid URL, since
     * the validation is pretty simple and doesn't verify the TLDs (there are
     * too many now to catch them all and new ones appear constantly).
     *
     * @param string $url
     *
     * @return bool TRUE if valid web syntax, otherwise FALSE.
     */
    public static function hasValidWebURLSyntax(
        $url)
    {
        return (bool) preg_match('/^https?:\/\/[^\s.\/]+\.[^\s.\/]{2}\S*$/iu', $url);
    }

    /**
     * Extract all URLs from a text string.
     *
     * This function is Unicode-aware.
     *
     * @param string $text The string to scan for URLs.
     *
     * @return array An array of URLs and their individual components.
     */
    public static function extractURLs(
        $text)
    {
        $urls = [];
        if (false !== preg_match_all(
            // NOTE: This disgusting regex comes from the Android SDK, slightly
            // modified by IG and then encoded by us into PHP regex format.
            '/((?:(http|https|Http|Https|rtsp|Rtsp):\/\/(?:(?:[a-zA-Z0-9$\-\_\.\+\!\*\'\(\)\,\;\?\&\=]|(?:\%[a-fA-F0-9]{2})){1,64}(?:\:(?:[a-zA-Z0-9$\-\_\.\+\!\*\'\(\)\,\;\?\&\=]|(?:\%[a-fA-F0-9]{2})){1,25})?\@)?)?((?:(?:[a-zA-Z0-9\x{00A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}\_][a-zA-Z0-9\x{00A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}\_\-]{0,64}\.)+(?:(?:aero|arpa|asia|a[cdefgilmnoqrstuwxz])|(?:biz|b[abdefghijmnorstvwyz])|(?:cat|com|coop|c[acdfghiklmnoruvxyz])|d[ejkmoz]|(?:edu|e[cegrstu])|f[ijkmor]|(?:gov|g[abdefghilmnpqrstuwy])|h[kmnrtu]|(?:info|int|i[delmnoqrst])|(?:jobs|j[emop])|k[eghimnprwyz]|l[abcikrstuvy]|(?:mil|mobi|museum|m[acdeghklmnopqrstuvwxyz])|(?:name|net|n[acefgilopruz])|(?:org|om)|(?:pro|p[aefghklmnrstwy])|qa|r[eosuw]|s[abcdeghijklmnortuvyz]|(?:tel|travel|t[cdfghjklmnoprtvwz])|u[agksyz]|v[aceginu]|w[fs]|(?:\x{03B4}\x{03BF}\x{03BA}\x{03B9}\x{03BC}\x{03AE}|\x{0438}\x{0441}\x{043F}\x{044B}\x{0442}\x{0430}\x{043D}\x{0438}\x{0435}|\x{0440}\x{0444}|\x{0441}\x{0440}\x{0431}|\x{05D8}\x{05E2}\x{05E1}\x{05D8}|\x{0622}\x{0632}\x{0645}\x{0627}\x{06CC}\x{0634}\x{06CC}|\x{0625}\x{062E}\x{062A}\x{0628}\x{0627}\x{0631}|\x{0627}\x{0644}\x{0627}\x{0631}\x{062F}\x{0646}|\x{0627}\x{0644}\x{062C}\x{0632}\x{0627}\x{0626}\x{0631}|\x{0627}\x{0644}\x{0633}\x{0639}\x{0648}\x{062F}\x{064A}\x{0629}|\x{0627}\x{0644}\x{0645}\x{063A}\x{0631}\x{0628}|\x{0627}\x{0645}\x{0627}\x{0631}\x{0627}\x{062A}|\x{0628}\x{06BE}\x{0627}\x{0631}\x{062A}|\x{062A}\x{0648}\x{0646}\x{0633}|\x{0633}\x{0648}\x{0631}\x{064A}\x{0629}|\x{0641}\x{0644}\x{0633}\x{0637}\x{064A}\x{0646}|\x{0642}\x{0637}\x{0631}|\x{0645}\x{0635}\x{0631}|\x{092A}\x{0930}\x{0940}\x{0915}\x{094D}\x{0937}\x{093E}|\x{092D}\x{093E}\x{0930}\x{0924}|\x{09AD}\x{09BE}\x{09B0}\x{09A4}|\x{0A2D}\x{0A3E}\x{0A30}\x{0A24}|\x{0AAD}\x{0ABE}\x{0AB0}\x{0AA4}|\x{0B87}\x{0BA8}\x{0BCD}\x{0BA4}\x{0BBF}\x{0BAF}\x{0BBE}|\x{0B87}\x{0BB2}\x{0B99}\x{0BCD}\x{0B95}\x{0BC8}|\x{0B9A}\x{0BBF}\x{0B99}\x{0BCD}\x{0B95}\x{0BAA}\x{0BCD}\x{0BAA}\x{0BC2}\x{0BB0}\x{0BCD}|\x{0BAA}\x{0BB0}\x{0BBF}\x{0B9F}\x{0BCD}\x{0B9A}\x{0BC8}|\x{0C2D}\x{0C3E}\x{0C30}\x{0C24}\x{0C4D}|\x{0DBD}\x{0D82}\x{0D9A}\x{0DCF}|\x{0E44}\x{0E17}\x{0E22}|\x{30C6}\x{30B9}\x{30C8}|\x{4E2D}\x{56FD}|\x{4E2D}\x{570B}|\x{53F0}\x{6E7E}|\x{53F0}\x{7063}|\x{65B0}\x{52A0}\x{5761}|\x{6D4B}\x{8BD5}|\x{6E2C}\x{8A66}|\x{9999}\x{6E2F}|\x{D14C}\x{C2A4}\x{D2B8}|\x{D55C}\x{AD6D}|xn\-\-0zwm56d|xn\-\-11b5bs3a9aj6g|xn\-\-3e0b707e|xn\-\-45brj9c|xn\-\-80akhbyknj4f|xn\-\-90a3ac|xn\-\-9t4b11yi5a|xn\-\-clchc0ea0b2g2a9gcd|xn\-\-deba0ad|xn\-\-fiqs8s|xn\-\-fiqz9s|xn\-\-fpcrj9c3d|xn\-\-fzc2c9e2c|xn\-\-g6w251d|xn\-\-gecrj9c|xn\-\-h2brj9c|xn\-\-hgbk6aj7f53bba|xn\-\-hlcj6aya9esc7a|xn\-\-j6w193g|xn\-\-jxalpdlp|xn\-\-kgbechtv|xn\-\-kprw13d|xn\-\-kpry57d|xn\-\-lgbbat1ad8j|xn\-\-mgbaam7a8h|xn\-\-mgbayh7gpa|xn\-\-mgbbh1a71e|xn\-\-mgbc0a9azcg|xn\-\-mgberp4a5d4ar|xn\-\-o3cw4h|xn\-\-ogbpf8fl|xn\-\-p1ai|xn\-\-pgbs0dh|xn\-\-s9brj9c|xn\-\-wgbh1c|xn\-\-wgbl6a|xn\-\-xkc2al3hye2a|xn\-\-xkc2dl3a5ee0h|xn\-\-yfro4i67o|xn\-\-ygbi2ammx|xn\-\-zckzah|xxx)|y[et]|z[amw]))|(?:(?:25[0-5]|2[0-4][0-9]|[0-1][0-9]{2}|[1-9][0-9]|[1-9])\.(?:25[0-5]|2[0-4][0-9]|[0-1][0-9]{2}|[1-9][0-9]|[1-9]|0)\.(?:25[0-5]|2[0-4][0-9]|[0-1][0-9]{2}|[1-9][0-9]|[1-9]|0)\.(?:25[0-5]|2[0-4][0-9]|[0-1][0-9]{2}|[1-9][0-9]|[0-9])))(?:\:\d{1,5})?)(\/(?:(?:[a-zA-Z0-9\x{00A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}\;\/\?\:\@\&\=\#\~\-\.\+\!\*\'\(\)\,\_])|(?:\%[a-fA-F0-9]{2}))*)?(?:\b|$)/iu',
            $text,
            $matches,
            PREG_SET_ORDER
        )) {
            foreach ($matches as $match) {
                $urls[] = [
                    'fullUrl'  => $match[0], // "https://foo:bar@www.bing.com/?foo=#test"
                    'baseUrl'  => $match[1], // "https://foo:bar@www.bing.com"
                    'protocol' => $match[2], // "https" (empty if no protocol)
                    'domain'   => $match[3], // "www.bing.com"
                    'path'     => isset($match[4]) ? $match[4] : '', // "/?foo=#test"
                ];
            }
        }

        return $urls;
    }
}
