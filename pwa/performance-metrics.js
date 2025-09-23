/**
 * Performance Metrics - Sistema CFC Bom Conselho
 * Coleta e valida métricas de performance do PWA
 */

class PerformanceMetrics {
    constructor() {
        this.metrics = {};
        this.observers = [];
        this.startTime = performance.now();
        this.init();
    }
    
    init() {
        console.log('[Performance] Inicializando coleta de métricas...');
        
        // Coletar métricas básicas
        this.collectBasicMetrics();
        
        // Configurar observers
        this.setupPerformanceObserver();
        this.setupResourceObserver();
        
        // Coletar métricas quando página carregar
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.collectDOMMetrics());
        } else {
            this.collectDOMMetrics();
        }
        
        // Coletar métricas quando página estiver completamente carregada
        window.addEventListener('load', () => this.collectLoadMetrics());
    }
    
    collectBasicMetrics() {
        // Navigation Timing
        const navigation = performance.getEntriesByType('navigation')[0];
        if (navigation) {
            this.metrics.navigation = {
                dns: navigation.domainLookupEnd - navigation.domainLookupStart,
                tcp: navigation.connectEnd - navigation.connectStart,
                request: navigation.responseStart - navigation.requestStart,
                response: navigation.responseEnd - navigation.responseStart,
                dom: navigation.domContentLoadedEventEnd - navigation.domContentLoadedEventStart,
                load: navigation.loadEventEnd - navigation.loadEventStart,
                total: navigation.loadEventEnd - navigation.fetchStart
            };
        }
        
        // Resource Timing
        const resources = performance.getEntriesByType('resource');
        this.metrics.resources = {
            total: resources.length,
            totalSize: resources.reduce((sum, r) => sum + (r.transferSize || 0), 0),
            css: resources.filter(r => r.name.includes('.css')).length,
            js: resources.filter(r => r.name.includes('.js')).length,
            images: resources.filter(r => /\.(jpg|jpeg|png|gif|webp|svg)$/i.test(r.name)).length
        };
        
        console.log('[Performance] Métricas básicas coletadas:', this.metrics);
    }
    
    setupPerformanceObserver() {
        if ('PerformanceObserver' in window) {
            // Observer para Core Web Vitals
            try {
                const observer = new PerformanceObserver((list) => {
                    for (const entry of list.getEntries()) {
                        this.handlePerformanceEntry(entry);
                    }
                });
                
                observer.observe({ entryTypes: ['largest-contentful-paint', 'first-input', 'layout-shift'] });
                this.observers.push(observer);
                
            } catch (error) {
                console.warn('[Performance] PerformanceObserver não suportado:', error);
            }
        }
    }
    
    setupResourceObserver() {
        if ('PerformanceObserver' in window) {
            try {
                const observer = new PerformanceObserver((list) => {
                    for (const entry of list.getEntries()) {
                        this.handleResourceEntry(entry);
                    }
                });
                
                observer.observe({ entryTypes: ['resource'] });
                this.observers.push(observer);
                
            } catch (error) {
                console.warn('[Performance] Resource Observer não suportado:', error);
            }
        }
    }
    
    handlePerformanceEntry(entry) {
        switch (entry.entryType) {
            case 'largest-contentful-paint':
                this.metrics.lcp = {
                    value: entry.startTime,
                    element: entry.element ? entry.element.tagName : 'unknown',
                    url: entry.url || 'unknown'
                };
                console.log('[Performance] LCP:', this.metrics.lcp);
                break;
                
            case 'first-input':
                this.metrics.fid = {
                    value: entry.processingStart - entry.startTime,
                    eventType: entry.name,
                    target: entry.target ? entry.target.tagName : 'unknown'
                };
                console.log('[Performance] FID:', this.metrics.fid);
                break;
                
            case 'layout-shift':
                if (!this.metrics.cls) this.metrics.cls = { value: 0, entries: [] };
                this.metrics.cls.value += entry.value;
                this.metrics.cls.entries.push({
                    value: entry.value,
                    hadRecentInput: entry.hadRecentInput,
                    sources: entry.sources ? entry.sources.map(s => s.node) : []
                });
                console.log('[Performance] CLS:', this.metrics.cls);
                break;
        }
    }
    
    handleResourceEntry(entry) {
        // Coletar informações sobre recursos carregados
        if (!this.metrics.resourceDetails) {
            this.metrics.resourceDetails = [];
        }
        
        this.metrics.resourceDetails.push({
            name: entry.name,
            duration: entry.duration,
            size: entry.transferSize || 0,
            type: this.getResourceType(entry.name),
            fromCache: entry.transferSize === 0
        });
    }
    
    getResourceType(url) {
        if (url.includes('.css')) return 'css';
        if (url.includes('.js')) return 'js';
        if (/\.(jpg|jpeg|png|gif|webp|svg)$/i.test(url)) return 'image';
        if (url.includes('.woff') || url.includes('.ttf')) return 'font';
        return 'other';
    }
    
    collectDOMMetrics() {
        // Métricas do DOM
        this.metrics.dom = {
            elements: document.querySelectorAll('*').length,
            images: document.querySelectorAll('img').length,
            scripts: document.querySelectorAll('script').length,
            stylesheets: document.querySelectorAll('link[rel="stylesheet"]').length,
            depth: this.getDOMDepth(document.documentElement)
        };
        
        console.log('[Performance] Métricas DOM coletadas:', this.metrics.dom);
    }
    
    collectLoadMetrics() {
        // Métricas de carregamento completo
        this.metrics.load = {
            time: performance.now() - this.startTime,
            timestamp: Date.now()
        };
        
        // Calcular TBT (Total Blocking Time) aproximado
        this.calculateTBT();
        
        // Calcular tamanho do App Shell
        this.calculateAppShellSize();
        
        console.log('[Performance] Métricas de carregamento coletadas:', this.metrics.load);
    }
    
    calculateTBT() {
        // Calcular TBT baseado em tasks longas
        const longTasks = performance.getEntriesByType('longtask') || [];
        this.metrics.tbt = {
            value: longTasks.reduce((sum, task) => sum + (task.duration - 50), 0),
            tasks: longTasks.length
        };
        
        console.log('[Performance] TBT calculado:', this.metrics.tbt);
    }
    
    calculateAppShellSize() {
        // Calcular tamanho do App Shell baseado nos recursos críticos
        const appShellResources = [
            '/admin/',
            '/admin/index.php',
            '/admin/assets/css/admin.css',
            '/admin/assets/css/mobile-menu-clean.css',
            '/admin/assets/js/admin.js',
            '/admin/assets/js/mobile-menu-clean.js',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'
        ];
        
        const resources = performance.getEntriesByType('resource');
        let appShellSize = 0;
        
        appShellResources.forEach(resource => {
            const entry = resources.find(r => r.name.includes(resource.split('/').pop()));
            if (entry) {
                appShellSize += entry.transferSize || 0;
            }
        });
        
        this.metrics.appShell = {
            size: appShellSize,
            sizeKB: Math.round(appShellSize / 1024),
            sizeMB: Math.round(appShellSize / (1024 * 1024) * 100) / 100
        };
        
        console.log('[Performance] App Shell size calculado:', this.metrics.appShell);
    }
    
    getDOMDepth(element, depth = 0) {
        let maxDepth = depth;
        for (let child of element.children) {
            maxDepth = Math.max(maxDepth, this.getDOMDepth(child, depth + 1));
        }
        return maxDepth;
    }
    
    // Validar métricas contra metas
    validateMetrics() {
        const results = {
            lcp: { target: 2500, actual: this.metrics.lcp?.value || 0, passed: false },
            cls: { target: 0.1, actual: this.metrics.cls?.value || 0, passed: false },
            tbt: { target: 200, actual: this.metrics.tbt?.value || 0, passed: false },
            appShell: { target: 1.5 * 1024 * 1024, actual: this.metrics.appShell?.size || 0, passed: false }
        };
        
        // Validar cada métrica
        results.lcp.passed = results.lcp.actual <= results.lcp.target;
        results.cls.passed = results.cls.actual <= results.cls.target;
        results.tbt.passed = results.tbt.actual <= results.tbt.target;
        results.appShell.passed = results.appShell.actual <= results.appShell.target;
        
        return results;
    }
    
    // Gerar relatório
    generateReport() {
        const validation = this.validateMetrics();
        
        return {
            timestamp: new Date().toISOString(),
            metrics: this.metrics,
            validation: validation,
            summary: {
                totalTests: Object.keys(validation).length,
                passed: Object.values(validation).filter(v => v.passed).length,
                failed: Object.values(validation).filter(v => !v.passed).length,
                score: Math.round((Object.values(validation).filter(v => v.passed).length / Object.keys(validation).length) * 100)
            }
        };
    }
    
    // Limpar observers
    cleanup() {
        this.observers.forEach(observer => observer.disconnect());
        this.observers = [];
    }
}

// Inicializar quando DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    if (window.location.pathname.includes('/admin/')) {
        window.performanceMetrics = new PerformanceMetrics();
        
        // Expor métricas globalmente para debug
        window.getPerformanceReport = () => {
            return window.performanceMetrics.generateReport();
        };
        
        console.log('[Performance] Sistema de métricas inicializado');
    }
});

// Exportar para uso global
if (typeof window !== 'undefined') {
    window.PerformanceMetrics = PerformanceMetrics;
}
