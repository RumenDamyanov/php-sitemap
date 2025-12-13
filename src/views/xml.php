<?= '<'.'?'.'xml version="1.0" encoding="UTF-8"?>'."\n" ?>
<?php if (null != $style) {
    echo '<'.'?'.'xml-stylesheet href="'.$style.'" type="text/xsl"?>'."\n";
} ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1" xmlns:video="http://www.google.com/schemas/sitemap-video/1.1">
<?php foreach ($items as $item) : ?>
	<url>
	<loc><?= isset($item['loc']) ? htmlspecialchars($item['loc'], ENT_QUOTES | ENT_XML1, 'UTF-8') : '' ?></loc>
    <?php

    if (! empty($item['translations'])) {
        foreach ($item['translations'] as $translation) {
            echo "\t\t".'<xhtml:link rel="alternate" hreflang="'.$translation['language'].'" href="'.$translation['url'].'" />'."\n";
        }
    }

    if (! empty($item['alternates'])) {
        foreach ($item['alternates'] as $alternate) {
            echo "\t\t".'<xhtml:link rel="alternate" media="'.$alternate['media'].'" href="'.$alternate['url'].'" />'."\n";
        }
    }

    if (!empty($item['priority'])) {
        echo "\t\t".'<priority>'.$item['priority'].'</priority>'."\n";
    }

    if (!empty($item['lastmod'])) {
        echo "\t\t".'<lastmod>'.date('Y-m-d\TH:i:sP', strtotime($item['lastmod'])).'</lastmod>'."\n";
    }

    if (!empty($item['freq'])) {
        echo "\t\t".'<changefreq>'.$item['freq'].'</changefreq>'."\n";
    }

    if (! empty($item['images'])) {
        foreach ($item['images'] as $image) {
            echo "\t\t".'<image:image>'."\n";
            echo "\t\t\t".'<image:loc>'.$image['url'].'</image:loc>'."\n";
            if (isset($image['title'])) {
                echo "\t\t\t".'<image:title>'.htmlspecialchars($image['title'], ENT_QUOTES | ENT_XML1, 'UTF-8').'</image:title>'."\n";
            }
            if (isset($image['caption'])) {
                echo "\t\t\t".'<image:caption>'.htmlspecialchars($image['caption'], ENT_QUOTES | ENT_XML1, 'UTF-8').'</image:caption>'."\n";
            }
            if (isset($image['geo_location'])) {
                echo "\t\t\t".'<image:geo_location>'.htmlspecialchars($image['geo_location'], ENT_QUOTES | ENT_XML1, 'UTF-8').'</image:geo_location>'."\n";
            }
            if (isset($image['license'])) {
                echo "\t\t\t".'<image:license>'.htmlspecialchars($image['license'], ENT_QUOTES | ENT_XML1, 'UTF-8').'</image:license>'."\n";
            }
            echo "\t\t".'</image:image>'."\n";
        }
    }

    if (! empty($item['videos'])) {
        foreach ($item['videos'] as $video) {
            echo "\t\t".'<video:video>'."\n";
            if (isset($video['thumbnail_loc'])) {
                echo "\t\t\t".'<video:thumbnail_loc>'.$video['thumbnail_loc'].'</video:thumbnail_loc>'."\n";
            }
            if (isset($video['title'])) {
                echo "\t\t\t".'<video:title><![CDATA['.str_replace("]]>", "]]&gt;", $video['title']).']]></video:title>'."\n";
            }
            if (isset($video['description'])) {
                echo "\t\t\t".'<video:description><![CDATA['.str_replace("]]>", "]]&gt;", $video['description']).']]></video:description>'."\n";
            }
            if (isset($video['content_loc'])) {
                echo "\t\t\t".'<video:content_loc>'.$video['content_loc'].'</video:content_loc>'."\n";
            }
            if (isset($video['duration'])) {
                echo "\t\t\t".'<video:duration>'.$video['duration'].'</video:duration>'."\n";
            }
            if (isset($video['expiration_date'])) {
                echo "\t\t\t".'<video:expiration_date>'.$video['expiration_date'].'</video:expiration_date>'."\n";
            }
            if (isset($video['rating'])) {
                echo "\t\t\t".'<video:rating>'.$video['rating'].'</video:rating>'."\n";
            }
            if (isset($video['view_count'])) {
                echo "\t\t\t".'<video:view_count>'.$video['view_count'].'</video:view_count>'."\n";
            }
            if (isset($video['publication_date'])) {
                echo "\t\t\t".'<video:publication_date>'.$video['publication_date'].'</video:publication_date>'."\n";
            }
            if (isset($video['family_friendly'])) {
                echo "\t\t\t".'<video:family_friendly>'.$video['family_friendly'].'</video:family_friendly>'."\n";
            }
            if (isset($video['requires_subscription'])) {
                echo "\t\t\t".'<video:requires_subscription>'.$video['requires_subscription'].'</video:requires_subscription>'."\n";
            }
            if (isset($video['live'])) {
                echo "\t\t\t".'<video:live>'.$video['live'].'</video:live>'."\n";
            }
            if (isset($video['player_loc'])) {
                echo "\t\t\t".'<video:player_loc allow_embed="'.$video['player_loc']['allow_embed'].'" autoplay="'.
                $video['player_loc']['autoplay'].'">'.$video['player_loc']['player_loc'].'</video:player_loc>'."\n";
            }
            if (isset($video['restriction'])) {
                echo "\t\t\t".'<video:restriction relationship="'.$video['restriction']['relationship'].'">'.$video['restriction']['restriction'].'</video:restriction>'."\n";
            }
            if (isset($video['gallery_loc'])) {
                echo "\t\t\t".'<video:gallery_loc title="'.$video['gallery_loc']['title'].'">'.$video['gallery_loc']['gallery_loc'].'</video:gallery_loc>'."\n";
            }
            if (isset($video['price'])) {
                echo "\t\t\t".'<video:price currency="'.$video['price']['currency'].'">'.$video['price']['price'].'</video:price>'."\n";
            }
            if (isset($video['uploader'])) {
                echo "\t\t\t".'<video:uploader info="'.$video['uploader']['info'].'">'.$video['uploader']['uploader'].'</video:uploader>'."\n";
            }
            echo "\t\t".'</video:video>'."\n";
        }
    }

    ?>
	</url>
<?php endforeach; ?>
</urlset>

