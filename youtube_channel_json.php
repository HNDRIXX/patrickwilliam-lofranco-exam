<?php
    try {
        $conn = new PDO("pgsql:host=localhost;dbname=youtube-db", 'postgres', '09082612');
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $channels_query = $conn->query("SELECT * FROM youtube_channels");
        $channels_data = $channels_query->fetchAll(PDO::FETCH_ASSOC);

        $videos_query = $conn->query("SELECT * FROM youtube_channel_videos");
        $videos_data = $videos_query->fetchAll(PDO::FETCH_ASSOC);

        $combined_data = [
            "youtube_channels" => $channels_data,
            "youtube_channel_videos" => $videos_data
        ];

        header('Content-Type: application/json');
        echo json_encode($combined_data);
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
?>
