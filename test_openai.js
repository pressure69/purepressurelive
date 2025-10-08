import OpenAI from "openai";
import "dotenv/config";

const openai = new OpenAI({
  baseURL: "https://openrouter.ai/api/v1",
  apiKey: process.env.OPENROUTER_API_KEY,
  defaultHeaders: {
    "Authorization": `Bearer ${process.env.OPENROUTER_API_KEY}`,
    "HTTP-Referer": "https://purepressurelive.com",
    "X-Title": "PurePressureLive",
  },
});

const question = process.argv[2] || "Hello AI!";

async function run() {
  try {
    const completion = await openai.chat.completions.create({
      model: "openai/gpt-4o",
      messages: [{ role: "user", content: question }],
      max_tokens: 500, // ✅ free tier friendly
    });

    console.log(completion.choices[0].message.content);
  } catch (err) {
    console.error("Error:", err.response?.data || err.message);
  }
}

run();
