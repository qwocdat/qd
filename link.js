export default async function handler(req, res) {
    // Mở khóa CORS để trình duyệt không chặn
    res.setHeader('Access-Control-Allow-Origin', '*');
    res.setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
    res.setHeader('Access-Control-Allow-Headers', 'Content-Type');
    
    if (req.method === 'OPTIONS') {
        return res.status(200).end();
    }

    const { url } = req.query;
    if (!url) return res.status(400).json({ error: 'Thiếu đường link đích' });

    // API CỦA BẠN ĐÃ ĐƯỢC GẮN
    const LINK4M_API = "6992df868b3d1e423d1ab833";
    const targetApiUrl = `https://link4m.com/api-shorten/v2?api=${LINK4M_API}&url=${encodeURIComponent(url)}`;

    try {
        // Vercel Server sẽ gọi Link4M thay cho trình duyệt của khách
        const response = await fetch(targetApiUrl);
        const data = await response.json();
        
        // Trả kết quả về
        res.status(200).json(data);
    } catch (error) {
        res.status(500).json({ error: "Lỗi kết nối từ Vercel đến Link4M" });
    }
}
