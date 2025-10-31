<?php

namespace App\Services;

use App\Models\Poem;
use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class PosterGenerator {
    /**
     * Generate poster image from provided render data
     *
     * @param array  $postData
     * @param string $dir
     * @param string $poemImgFileName
     * @param string $posterPath
     * @param string $compositionID
     * @param bool   $force
     * @param Poem   $poem
     * @return bool
     * @throws Exception
     */
    public function generatePosterFromData(array $postData, string $dir, string $poemImgFileName, string $posterPath, string $compositionID, bool $force, Poem $poem): bool {
        // Input validation
        if (empty($postData) || empty($dir) || empty($poemImgFileName) || empty($posterPath)) {
            Log::warning('PosterGenerator: Invalid input parameters', [
                'poem_id'     => $poem->id,
                'dir'         => $dir,
                'filename'    => $poemImgFileName,
                'poster_path' => $posterPath,
            ]);

            throw new Exception('PosterGenerator: Incomplete poster generation parameters');
        }

        try {
            // Ensure directory exists
            if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
                throw new Exception('PosterGenerator: Cannot create directory');
            }

            $poemImgPath = $this->fetchPoemImg($postData, $dir, $poemImgFileName, $force);

            $scale = 1.0;
            if (isset($postData['config']['scale']) && is_numeric($postData['config']['scale'])) {
                $candidate = (float) $postData['config']['scale'];
                if ($candidate > 0) {
                    $scale = $candidate;
                }
            }

            $scene          = $poem->is_campaign ? ($poem->campaign_id . '-' . $poem->id) : $poem->id;
            $page           = $poem->is_campaign ? 'pages/campaign/campaign' : 'pages/poems/index';
            $appCodeImgPath = (new Weapp())->fetchAppCodeImg($scene, $dir, $page);

            if (!$this->composite($poemImgPath, $appCodeImgPath, $posterPath, $compositionID, $scale)) {
                Log::error('PosterGenerator: Composite failed', [
                    'poem_id'           => $poem->id,
                    'poem_img_path'     => $poemImgPath,
                    'app_code_img_path' => $appCodeImgPath,
                    'poster_path'       => $posterPath,
                ]);

                throw new Exception('PosterGenerator: Composite failed');
            }

            return true;

        } catch (Exception $e) {
            Log::error('PosterGenerator: Generation failed', [
                'poem_id' => $poem->id,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Fetch poem image from render server
     */
    private function fetchPoemImg(array $postData, string $dir, string $poemImgFileName, bool $force = false): string {
        $poemImgPath = $dir . '/' . $poemImgFileName;

        if (!$force && file_exists($poemImgPath)) {
            return $poemImgPath;
        }

        $poemImg = file_get_contents_post(config('app.render_server'), $postData, 'application/json', 15);

        if ($poemImg === false) {
            throw new Exception('PosterGenerator: Cannot fetch image from render server');
        }

        if (!file_put_contents($poemImgPath, $poemImg)) {
            throw new Exception('PosterGenerator: Cannot write image to file');
        }

        // Verify the generated file is actually an image
        $mimeType = File::mimeType($poemImgPath);
        if ($mimeType === 'text/plain') {
            @unlink($poemImgPath);

            throw new Exception('PosterGenerator: Failed to generate image, please try again later.');
        }

        if (!in_array($mimeType, ['image/jpeg', 'image/png', 'image/gif'])) {
            @unlink($poemImgPath);

            throw new Exception('PosterGenerator: Generated file is not a valid image format: ' . $mimeType);
        }

        return $poemImgPath;
    }

    /**
     * Composite poem image with app code image.
     *
     * @param float $scale Render scale factor applied to the poem image
     */
    private function composite(string $poemImgPath, string $appCodeImgPath, string $posterPath, string $compositionID = 'pure', float $scale = 1.0): bool {
        // Validate input files exist
        if (!file_exists($poemImgPath)) {
            Log::error('PosterGenerator: Poem image not found', ['path' => $poemImgPath]);

            return false;
        }

        if (!file_exists($appCodeImgPath)) {
            Log::error('PosterGenerator: App code image not found', ['path' => $appCodeImgPath]);

            return false;
        }

        $params = $this->getCompositionParams();

        if (!isset($params[$compositionID])) {
            Log::error('PosterGenerator: Invalid composition ID', ['composition_id' => $compositionID]);

            return false;
        }

        $param = $this->applyScaleToParams($params[$compositionID], $scale);

        $posterImg = null;

        try {
            /** @var \GdImage|false $posterImg */
            $posterImg = img_overlay($poemImgPath, $appCodeImgPath, $param['x'], $param['y'], $param['width'], $param['height']);

            if (!$posterImg) {
                Log::error('PosterGenerator: img_overlay failed');

                return false;
            }

            // Detect poem image type and save poster in the same format
            // Default quality for different formats
            $quality = 100; // for JPEG (0-100)

            $imgType = exif_imagetype($poemImgPath);
            if ($imgType === IMAGETYPE_JPEG) {
                $res    = imagejpeg($posterImg, $posterPath, $quality);
                $format = 'JPEG';
            } elseif ($imgType === IMAGETYPE_PNG) {
                // PNG quality: 0 (no compression) to 9 (max compression)
                // Convert JPEG quality (0-100) to PNG quality (0-9)
                $pngQuality = (int) ($quality * 9 / 100);
                $res        = imagepng($posterImg, $posterPath, 0);
                $format     = 'PNG';
            } elseif ($imgType === IMAGETYPE_GIF) {
                $res    = imagegif($posterImg, $posterPath);
                $format = 'GIF';
            } else {
                throw new Exception('PosterGenerator: Image type not supported, poem image type: ' . $imgType);
            }

            if (!$res) {
                Log::error('PosterGenerator: Failed to save poster image', [
                    'path'   => $posterPath,
                    'format' => $format,
                ]);

                return false;
            }

            return true;

        } finally {
            // Clean up resources
            if ($posterImg) {
                imagedestroy($posterImg);
            }
        }
    }

    /**
     * Get composition parameters configuration
     */
    private function getCompositionParams(): array {
        return [
            'pure' => [
                'x'      => 220,
                'y'      => 160,
                'width'  => 120,
                'height' => 120,
            ],
            'nft' => [
                'x'      => 220,
                'y'      => 250,
                'width'  => 166,
                'height' => 166,
            ],
        ];
    }

    /**
     * Adjust composition parameters based on render scale factor.
     */
    private function applyScaleToParams(array $params, float $scale): array {
        $normalizedScale = $scale > 0 ? $scale : 1.0;

        if ($normalizedScale === 1.0) {
            return $params;
        }

        return [
            'x'      => max(0, (int) round($params['x'] * $normalizedScale)),
            'y'      => max(0, (int) round($params['y'] * $normalizedScale)),
            'width'  => max(1, (int) round($params['width'] * $normalizedScale)),
            'height' => max(1, (int) round($params['height'] * $normalizedScale)),
        ];
    }
}
