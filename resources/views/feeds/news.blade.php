<?php echo '<?xml version="1.0" encoding="UTF-8"?>'."\n"; ?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
    <channel>
        <title>{{ $title }}</title>
        <link>{{ $home }}</link>
        <description>{{ $description }}</description>
        <language>{{ $locale }}</language>
        <atom:link href="{{ $self }}" rel="self" type="application/rss+xml" />
        @foreach ($items as $item)
            <item>
                <title>{{ $item['title'] }}</title>
                <link>{{ $item['link'] }}</link>
                <guid isPermaLink="true">{{ $item['link'] }}</guid>
                @if ($item['description'])
                    <description>{{ $item['description'] }}</description>
                @endif
                @if ($item['published_at'])
                    <pubDate>{{ $item['published_at']->toRssString() }}</pubDate>
                @endif
            </item>
        @endforeach
    </channel>
</rss>
