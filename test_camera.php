<!DOCTYPE html> <html> <head> <title>Camera Test</title> 
</head> <body style="background:#000;color:#fff;">
  <h1>Camera Test</h1> <video id="cam" autoplay playsinline 
  style="width:90%;border:2px solid red;"></video> <script>
    navigator.mediaDevices.getUserMedia({ video: true, audio: 
    true })
      .then(stream => { 
        document.getElementById('cam').srcObject = stream;
      })
      .catch(err => { alert("Camera error: " + err.message); 
        console.error(err);
      });
  </script> </body>
</html>
