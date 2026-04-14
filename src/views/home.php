<style>
.warehouse-home {
    height: 100vh;
    width: 100%;
    background-image: url('img/home_beverage.png');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
}

.warehouse-home__overlay {
    height: 100%;
    width: 100%;
    background: linear-gradient(to right, rgba(10, 30, 31, 0.4) 0%, rgba(217, 225, 203, 0.2) 100%);
    display: flex;
    align-items: center;
    justify-content: flex-start; /* Dồn về bên trái (gần sidebar) */
    padding-left: 10%;
}

.warehouse-home__panel {
    padding: 60px;
    border-radius: 40px;
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    box-shadow: 20px 25px 60px rgba(0,0,0,0.2);
    text-align: left; /* Căn trái cho hài hòa với sidebar */
    max-width: 800px;
    animation: slideInLeft 1s ease-out;
}

.warehouse-home__slogan {
    font-size: 3.8rem;
    font-weight: 900;
    margin-bottom: 25px;
    background: linear-gradient(to right, #b99330, #ffffff, #f8c291);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    filter: drop-shadow(0 4px 10px rgba(0,0,0,0.3));
    letter-spacing: 2px;
    line-height: 1.2;
}

.warehouse-home__instruction {
    font-size: 1.4rem;
    color: #ffffff;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 15px;
    margin-top: 30px;
}

.arrow-pointer {
    font-size: 2rem;
    color: #b99330;
    animation: bounceLeft 1.5s infinite;
}

@keyframes slideInLeft {
    from { opacity: 0; transform: translateX(-50px); }
    to { opacity: 1; transform: translateX(0); }
}

@keyframes bounceLeft {
    0%, 100% { transform: translateX(0); }
    50% { transform: translateX(-15px); }
}

@media (max-width: 768px) {
    .warehouse-home__slogan { font-size: 2rem; }
    .warehouse-home__instruction { font-size: 1rem; }
    .warehouse-home { padding-left: 20px; }
    .warehouse-home__panel { padding: 40px 20px; }
}
</style>

<div class="warehouse-home">
    <div class="warehouse-home__overlay">
        <div class="warehouse-home__panel">
            <h1 class="warehouse-home__slogan">Uống đã – Ăn ngon – Chill trọn</h1>
            <div class="warehouse-home__instruction">
                <i class="fa-solid fa-circle-arrow-left arrow-pointer"></i> 
                <span>Chọn chức năng tại menu bên trái để thực hiện</span>
            </div>
        </div>
    </div>
</div>
