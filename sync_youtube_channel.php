<?php
    $youtube_api_key = 'AIzaSyDVF2U_RQuQcBzzQ81bpvjoYZMpdqepVvs';
    $channel_id = 'UC5664f6TkaeHgwBly50DWZQ';

    // NBA
    // UCWJ2lWNubArHWmf3FIHbfcQ

    // News Channel
    // UC5664f6TkaeHgwBly50DWZQ
    
    // Marvel
    // UCvC4D8onUfXzvjTOM-dBfEA
    try {
        $conn = new PDO("pgsql:host=localhost;dbname=youtube-db", 'postgres', '09082612');
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $channel_api_url = "https://www.googleapis.com/youtube/v3/channels?part=snippet&id={$channel_id}&key={$youtube_api_key}";

        // I use videoDuration=medium to get the medium duration videos.
        $video_api_url = "https://www.googleapis.com/youtube/v3/search?part=snippet&channelId={$channel_id}&maxResults=50&order=date&type=video&videoDuration=medium&key={$youtube_api_key}";

        $video_data = array();
        $context = stream_context_create(["http" => ["ignore_errors" => true]]);

        $video_response = file_get_contents($video_api_url, false, $context);
        $channel_response = file_get_contents($channel_api_url, false, $context);

        $data = json_decode($video_response, true);
        $channel_data = json_decode($channel_response, true);

        if (isset($data['items'])) {
            $video_data = $data['items'];
            
            do {
                $page_token = $data['nextPageToken'];
                $more_video = $video_api_url . "&pageToken={$page_token}";
        
                $more_video_response = file_get_contents($more_video, false, $context);
                $data = json_decode($more_video_response, true);
        
                if (isset($data['items'])) {
                    $video_data = array_merge($video_data, $data['items']);
                } else { break; }
                
            } while (count($video_data) < 100 && isset($data['nextPageToken']));
        }

        if (!empty($channel_data['items']) || !empty($video_data['items'])) {
            $channel_id = $conn->query("SELECT COUNT(*) FROM youtube_channels")->fetchColumn() + 1;

            $profile_pic = $channel_data['items'][0]['snippet']['thumbnails']['high']['url'];
            $channel_name = $channel_data['items'][0]['snippet']['title'];
            $description = $channel_data['items'][0]['snippet']['description'];

            $sql = "INSERT INTO youtube_channels (id, channel_name, profile_pic, description) 
            VALUES (:channel_id, :channel_name, :profile_pic, :description)";

            $query = $conn->prepare($sql);
            $query->bindParam(':channel_id', $channel_id);
            $query->bindParam(':channel_name', $channel_name);
            $query->bindParam(':profile_pic', $profile_pic);
            $query->bindParam(':description', $description);
            $query->execute();

            foreach ($video_data as $video) {
                $video_title = $video['snippet']['title'];
                $video_description = $video['snippet']['description'];
                $video_link = "https://www.youtube.com/watch?v={$video['id']['videoId']}";
                $video_thumbnail = $video['snippet']['thumbnails']['high']['url'];
    
                $sql = "INSERT INTO youtube_channel_videos (channel_id, video_title, video_desc, video_link, video_thumbnail) VALUES (:channel_id, :title, :description, :link, :thumbnail)";
    
                $query = $conn->prepare($sql);
                $query->bindParam(':channel_id', $channel_id);
                $query->bindParam(':title', $video_title);
                $query->bindParam(':description', $video_description);
                $query->bindParam(':link', $video_link);
                $query->bindParam(':thumbnail', $video_thumbnail);
                $query->execute();
            }
        }else {
            echo "Something Went Wrong.";
        }

        header("Location: index.html");
        exit();
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
?>