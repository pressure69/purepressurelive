<?php

session_start(); ?>
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Public Chat</title>
<style>
body{background:#000;color:#fff;font-family:Arial;}
#chat-box{height:400px;overflow-y:auto;border:1px solid #900;padding:10px;margin:10px;}
</style>
</head>
<body>
<h2>Public Chatroom</h2>
<div id="chat-box"></div>
<form id="chat-form">
<input type="text" id="message" placeholder="Type..." required>
<button type="submit">Send</button>
</form>
<script>
const box=document.getElementById('chat-box');
const form=document.getElementById('chat-form');
form.addEventListener('submit',e=>{
e.preventDefault();
const msg=document.getElementById('message').value.trim();
if(!msg)return;
const div=document.createElement('div');
div.textContent='<?=$_SESSION['username'] ?? 'Guest'?>: '+msg;
box.appendChild(div);
box.scrollTop=box.scrollHeight;
form.reset();
});
</script>
</body>
</html>
