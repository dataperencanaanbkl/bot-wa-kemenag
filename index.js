const express = require('express');
const axios = require('axios');
const app = express();

app.use(express.json());
app.use(express.urlencoded({ extended: true }));

const TOKEN_FONNTE = '4wmbaY2g78JSi1VyW17s';
const API_KEY_GEMINI = 'AQ.Ab8RN6KwKgaS8lzsqylWwPhd7NklF3JEHowIi6dU5vmCa7Tvuw';

app.get('/', (req, res) => res.send('Webhook Active!'));

app.post('/', async (req, res) => {
    const pengirim = req.body.sender;
    const pesan = req.body.message ? req.body.message.trim() : '';

    if (!pengirim || !pesan) {
        return res.sendStatus(200);
    }

    const pesanLower = pesan.toLowerCase();
    if (pesanLower === 'aiohumas' || pesanLower === 'menu' || pesanLower === 'halo') {
        const balasanAwal = "Hai! Sahabat Layanan Kemenag. Ada yang bisa kami bantu hari ini? 😊\n\n"
                          + "Silakan ajukan pertanyaan seputar administrasi, berkas bulanan, atau layanan publik lainnya.";
        await kirimPesanFonnte(pengirim, balasanAwal);
        return res.sendStatus(200);
    }

    const jawabanAI = await tanyaGemini(pesan);
    await kirimPesanFonnte(pengirim, jawabanAI);
    res.sendStatus(200);
});

async function tanyaGemini(promptUser) {
    const url = `https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=${API_KEY_GEMINI}`;
    const systemPrompt = "Kamu adalah asisten virtual resmi Humas Kementerian Agama (Kemenag). "
                       + "Tugasmu melayani pertanyaan pegawai dan masyarakat secara sopan, ramah, ringkas, dan solutif. "
                       + "Format jawaban rapi dan mudah dibaca via WhatsApp. "
                       + "Jika ada pertanyaan di luar wewenang, arahkan pengguna untuk menghubungi admin kepegawaian langsung.";

    try {
        const response = await axios.post(url, {
            contents: [{
                role: 'user',
                parts: [{ text: `${systemPrompt}\n\nPertanyaan Masuk: ${promptUser}` }]
            }],
            generationConfig: { temperature: 0.4, maxOutputTokens: 500 }
        });
        return response.data.candidates[0].content.parts[0].text;
    } catch (err) {
        return "Maaf, sistem layanan AI sedang mengalami kendala koneksi. Coba lagi sesaat ya.";
    }
}

async function kirimPesanFonnte(nomor, pesan) {
    try {
        await axios.post('https://api.fonnte.com/send', {
            target: nomor,
            message: pesan
        }, {
            headers: { Authorization: TOKEN_FONNTE }
        });
    } catch (err) {
        console.error('Error sending message:', err.message);
    }
}

const PORT = process.env.PORT || 3000;
app.listen(PORT, () => console.log(`Server running on port ${PORT}`));
