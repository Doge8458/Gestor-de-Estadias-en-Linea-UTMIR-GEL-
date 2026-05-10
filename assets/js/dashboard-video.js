var player;
var estadoVideoVisto = (window.dashboardData && window.dashboardData.videoVisto) ? 1 : 0;

function onYouTubeIframeAPIReady() {
    player = new YT.Player('youtubePlayer', {
        height: '360',
        width: '100%',
        videoId: 'azKiV4fMksY',
        playerVars: {
            controls: 0,
            disablekb: 1,
            rel: 0,
            modestbranding: 1
        },
        events: {
            onStateChange: onPlayerStateChange
        }
    });
}

function onPlayerStateChange(event) {
    if (event.data == YT.PlayerState.ENDED && estadoVideoVisto == 0) {
        document.getElementById('btnCerrarVideo').style.display = 'block';

        fetch('api/marcar_video.php', { method: 'POST' })
            .then(res => res.json())
            .then(data => {
                console.log('Respuesta del servidor:', data);
            })
            .catch(error => console.error('Error al guardar el estado del video:', error));

        estadoVideoVisto = 1;
    }
}

function abrirVideoTutorial() {
    document.getElementById('videoModal').classList.add('active');
}
