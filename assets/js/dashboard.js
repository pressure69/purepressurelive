document.addEventListener("DOMContentLoaded", () => {
  const streamStatus = document.getElementById("streamStatus");
  const goLiveBtn = document.getElementById("goLiveBtn");
  const endLiveBtn = document.getElementById("endLiveBtn");
  const saveSettings = document.getElementById("saveSettings");
  const roomMode = document.getElementById("roomMode");

  // === Stream Buttons ===
  goLiveBtn.addEventListener("click", () => {
    streamStatus.textContent = "LIVE 🔴";
    streamStatus.style.color = "red";
    console.log("Stream started!");
    // TODO: connect OBS/WebRTC backend
  });

  endLiveBtn.addEventListener("click", () => {
    streamStatus.textContent = "Offline";
    streamStatus.style.color = "gray";
    console.log("Stream ended.");
    // TODO: disconnect backend
  });

  // === Room Settings ===
  saveSettings.addEventListener("click", () => {
    alert("Room mode saved: " + roomMode.value);
    // TODO: push to backend
  });

  // === Filters ===
  window.applyFilter = function(type) {
    alert("Applying filter: " + type);
    console.log("Filter applied:", type);
    // TODO: integrate with MediaPipe / WebRTC
  };

  // === AI Widget ===
  const aiBody = document.getElementById("aiBody");
  const aiInput = document.getElementById("aiInput");
  const aiSend = document.getElementById("aiSend");
  const aiMinimize = document.getElementById("aiMinimize");

  aiSend.addEventListener("click", () => {
    const msg = aiInput.value.trim();
    if (msg) {
      aiBody.innerHTML += `<p><strong>You:</strong> ${msg}</p>`;
      aiInput.value = "";
      // TODO: Send to backend AI
      aiBody.innerHTML += `<p><strong>AI:</strong> (response here)</p>`;
    }
  });

  aiMinimize.addEventListener("click", () => {
    aiBody.style.display =
      aiBody.style.display === "none" ? "block" : "none";
  });
});
