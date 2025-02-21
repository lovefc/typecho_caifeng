/*
 * @Author       : lovefc
 * @Date         : 2025-02-04 23:37:17
 * @LastEditTime : 2025-02-17 22:29:21
 */


// 波纹动画
const style = document.createElement('style');
style.textContent = `
            @keyframes rippleEffect {
                from {
                    transform: scale(0);
                    opacity: 1;
                }
                to {
                    transform: scale(10);
                    opacity: 0;
                }
            }
        `;
document.head.appendChild(style);

// 滚动动画
window.addEventListener('scroll', () => {
    const articles = document.querySelectorAll('.article');
    articles.forEach(article => {
        const articleTop = article.getBoundingClientRect().top;
        if (articleTop < window.innerHeight * 0.8) {
            article.style.opacity = '1';
            article.style.transform = 'translateY(0)';
        }
    });
});

class BackToTop {
    constructor(options = {}) {
        // 默认配置
        const defaultOptions = {
            buttonId: 'back-to-top', // 按钮ID
            scrollThreshold: 100, // 滚动触发距离
            buttonText: '↑', // 按钮文字
            buttonClass: 'back-to-top', // 按钮样式类
        };
        this.options = {
            ...defaultOptions,
            ...options
        };

        // 创建按钮
        this.createButton();
        this.init();
    }

    // 创建按钮
    createButton() {
        const button = document.createElement('button');
        button.id = this.options.buttonId;
        button.className = this.options.buttonClass;
        button.innerHTML = this.options.buttonText;
        button.title = 'Go to top';
        document.body.appendChild(button);
        this.button = button;
    }

    // 初始化
    init() {
        window.addEventListener('scroll', () => this.toggleButtonVisibility());
        this.button.addEventListener('click', () => this.scrollToTop());
    }

    // 控制按钮显示/隐藏
    toggleButtonVisibility() {
        if (window.scrollY > this.options.scrollThreshold) {
            this.button.style.display = 'block';
        } else {
            this.button.style.display = 'none';
        }
    }

    // 平滑滚动到顶部
    scrollToTop() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }
}

// 初始化组件
const backToTopButton = new BackToTop({
    scrollThreshold: 100, // 滚动超过100px时显示按钮
    buttonText: '↑', // 按钮文字
});