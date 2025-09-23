/**
 * Automated PWA Test Suite - Sistema CFC Bom Conselho
 * Executa testes automatizados e gera relatório
 */

class AutomatedPWATest {
    constructor() {
        this.results = {};
        this.startTime = Date.now();
        this.testCount = 0;
        this.passedCount = 0;
        this.failedCount = 0;
    }
    
    async runAllTests() {
        console.log('🚀 Iniciando testes automatizados PWA...');
        
        try {
            // Teste 1: Manifest
            await this.testManifest();
            
            // Teste 2: Service Worker
            await this.testServiceWorker();
            
            // Teste 3: Instalação
            await this.testInstallation();
            
            // Teste 4: Offline
            await this.testOffline();
            
            // Teste 5: Atualização
            await this.testUpdate();
            
            // Teste 6: Mobile Navigation
            await this.testMobileNavigation();
            
            // Teste 7: Segurança
            await this.testSecurity();
            
            // Teste 8: Acessibilidade
            await this.testAccessibility();
            
            // Teste 9: Performance
            await this.testPerformance();
            
            // Teste 10: Cache
            await this.testCache();
            
            // Gerar relatório final
            this.generateReport();
            
        } catch (error) {
            console.error('❌ Erro durante execução dos testes:', error);
        }
    }
    
    async testManifest() {
        console.log('📋 Testando Manifest...');
        
        try {
            const response = await fetch('../pwa/manifest.json');
            const manifest = await response.json();
            
            const checks = {
                hasName: !!manifest.name,
                hasShortName: !!manifest.short_name,
                hasStartUrl: !!manifest.start_url,
                hasScope: !!manifest.scope,
                hasDisplay: manifest.display === 'standalone',
                hasIcons: Array.isArray(manifest.icons) && manifest.icons.length > 0,
                hasThemeColor: !!manifest.theme_color,
                hasBackgroundColor: !!manifest.background_color
            };
            
            const passed = Object.values(checks).every(check => check);
            
            this.recordTest('manifest', passed, {
                checks,
                manifest: manifest
            });
            
        } catch (error) {
            this.recordTest('manifest', false, { error: error.message });
        }
    }
    
    async testServiceWorker() {
        console.log('⚙️ Testando Service Worker...');
        
        try {
            const registration = await navigator.serviceWorker.getRegistration();
            const hasRegistration = !!registration;
            
            const swResponse = await fetch('../pwa/sw.js');
            const swText = await swResponse.text();
            
            const checks = {
                hasRegistration: hasRegistration,
                hasCacheVersion: swText.includes('CACHE_VERSION'),
                hasAppShell: swText.includes('APP_SHELL'),
                hasExclusions: swText.includes('EXCLUDED_ROUTES'),
                hasSkipWaiting: swText.includes('skipWaiting'),
                hasOfflinePage: swText.includes('OFFLINE_PAGE')
            };
            
            const passed = Object.values(checks).every(check => check);
            
            this.recordTest('serviceWorker', passed, {
                checks,
                registration: hasRegistration ? {
                    scope: registration.scope,
                    active: !!registration.active,
                    waiting: !!registration.waiting
                } : null
            });
            
        } catch (error) {
            this.recordTest('serviceWorker', false, { error: error.message });
        }
    }
    
    async testInstallation() {
        console.log('📱 Testando Instalação...');
        
        try {
            const checks = {
                hasManifest: !!document.querySelector('link[rel="manifest"]'),
                hasAppleMeta: !!document.querySelector('meta[name="apple-mobile-web-app-capable"]'),
                hasThemeColor: !!document.querySelector('meta[name="theme-color"]'),
                hasIcons: document.querySelectorAll('link[rel="icon"], link[rel="apple-touch-icon"]').length > 0,
                hasPWAManager: typeof window.PWAManager !== 'undefined'
            };
            
            const passed = Object.values(checks).every(check => check);
            
            this.recordTest('installation', passed, { checks });
            
        } catch (error) {
            this.recordTest('installation', false, { error: error.message });
        }
    }
    
    async testOffline() {
        console.log('📡 Testando Funcionalidade Offline...');
        
        try {
            const offlinePageResponse = await fetch('../pwa/offline.html');
            const hasOfflinePage = offlinePageResponse.ok;
            
            const swResponse = await fetch('../pwa/sw.js');
            const swText = await swResponse.text();
            
            const checks = {
                hasOfflinePage: hasOfflinePage,
                hasOfflineStrategy: swText.includes('networkFirstWithOfflineFallback'),
                hasCacheStrategy: swText.includes('cacheFirstStrategy'),
                hasStaleWhileRevalidate: swText.includes('staleWhileRevalidateStrategy')
            };
            
            const passed = Object.values(checks).every(check => check);
            
            this.recordTest('offline', passed, { checks });
            
        } catch (error) {
            this.recordTest('offline', false, { error: error.message });
        }
    }
    
    async testUpdate() {
        console.log('🔄 Testando Sistema de Atualização...');
        
        try {
            const swResponse = await fetch('../pwa/sw.js');
            const swText = await swResponse.text();
            
            const checks = {
                hasVersioning: swText.includes('CACHE_VERSION'),
                hasSkipWaiting: swText.includes('skipWaiting'),
                hasUpdateEvent: swText.includes('updatefound'),
                hasPWAManager: typeof window.PWAManager !== 'undefined'
            };
            
            const passed = Object.values(checks).every(check => check);
            
            this.recordTest('update', passed, { checks });
            
        } catch (error) {
            this.recordTest('update', false, { error: error.message });
        }
    }
    
    async testMobileNavigation() {
        console.log('📱 Testando Navegação Mobile...');
        
        try {
            const checks = {
                hasMobileToggle: !!document.querySelector('.mobile-menu-toggle'),
                hasMobileDrawer: !!document.querySelector('.mobile-drawer'),
                hasMobileJS: !!document.querySelector('script[src*="mobile-menu-clean.js"]'),
                hasAriaLabels: document.querySelectorAll('[aria-label]').length > 0,
                hasFocusManagement: document.querySelectorAll('[tabindex]').length > 0
            };
            
            const passed = Object.values(checks).every(check => check);
            
            this.recordTest('mobileNavigation', passed, { checks });
            
        } catch (error) {
            this.recordTest('mobileNavigation', false, { error: error.message });
        }
    }
    
    async testSecurity() {
        console.log('🔒 Testando Segurança...');
        
        try {
            const swResponse = await fetch('../pwa/sw.js');
            const swText = await swResponse.text();
            
            const checks = {
                hasExclusions: swText.includes('EXCLUDED_ROUTES'),
                hasExclusionCheck: swText.includes('shouldExcludeFromCache'),
                hasSecureScope: swText.includes('scope: \'/admin/\''),
                hasCSP: !!document.querySelector('meta[http-equiv="Content-Security-Policy"]')
            };
            
            const passed = Object.values(checks).every(check => check);
            
            this.recordTest('security', passed, { checks });
            
        } catch (error) {
            this.recordTest('security', false, { error: error.message });
        }
    }
    
    async testAccessibility() {
        console.log('♿ Testando Acessibilidade...');
        
        try {
            const checks = {
                hasLandmarks: document.querySelectorAll('header, nav, main, footer').length > 0,
                hasAriaLabels: document.querySelectorAll('[aria-label]').length > 0,
                hasAriaExpanded: document.querySelectorAll('[aria-expanded]').length > 0,
                hasFocusableElements: document.querySelectorAll('button, input, select, textarea, a[href]').length > 0,
                hasAltText: document.querySelectorAll('img[alt]').length > 0
            };
            
            const passed = Object.values(checks).every(check => check);
            
            this.recordTest('accessibility', passed, { checks });
            
        } catch (error) {
            this.recordTest('accessibility', false, { error: error.message });
        }
    }
    
    async testPerformance() {
        console.log('⚡ Testando Performance...');
        
        try {
            const checks = {
                hasPerformanceMetrics: typeof window.PerformanceMetrics !== 'undefined',
                hasLCP: !!window.performanceMetrics?.metrics?.lcp,
                hasCLS: !!window.performanceMetrics?.metrics?.cls,
                hasTBT: !!window.performanceMetrics?.metrics?.tbt,
                hasAppShell: !!window.performanceMetrics?.metrics?.appShell
            };
            
            const passed = Object.values(checks).every(check => check);
            
            this.recordTest('performance', passed, { 
                checks,
                metrics: window.performanceMetrics?.metrics || null
            });
            
        } catch (error) {
            this.recordTest('performance', false, { error: error.message });
        }
    }
    
    async testCache() {
        console.log('💾 Testando Cache...');
        
        try {
            const swResponse = await fetch('../pwa/sw.js');
            const swText = await swResponse.text();
            
            const checks = {
                hasCacheVersion: swText.includes('CACHE_VERSION'),
                hasCacheCleanup: swText.includes('caches.delete'),
                hasCacheStrategies: swText.includes('cacheFirstStrategy') && swText.includes('networkFirstStrategy'),
                hasAppShellCache: swText.includes('APP_SHELL')
            };
            
            const passed = Object.values(checks).every(check => check);
            
            this.recordTest('cache', passed, { checks });
            
        } catch (error) {
            this.recordTest('cache', false, { error: error.message });
        }
    }
    
    recordTest(testName, passed, details = {}) {
        this.testCount++;
        if (passed) {
            this.passedCount++;
        } else {
            this.failedCount++;
        }
        
        this.results[testName] = {
            passed,
            details,
            timestamp: new Date().toISOString()
        };
        
        console.log(`${passed ? '✅' : '❌'} ${testName}: ${passed ? 'PASSOU' : 'FALHOU'}`);
    }
    
    generateReport() {
        const duration = Date.now() - this.startTime;
        const score = Math.round((this.passedCount / this.testCount) * 100);
        
        const report = {
            summary: {
                totalTests: this.testCount,
                passed: this.passedCount,
                failed: this.failedCount,
                score: score,
                duration: duration,
                timestamp: new Date().toISOString()
            },
            results: this.results,
            recommendations: this.generateRecommendations()
        };
        
        console.log('📊 Relatório Final:');
        console.log(`✅ Testes aprovados: ${this.passedCount}`);
        console.log(`❌ Testes falharam: ${this.failedCount}`);
        console.log(`📈 Score: ${score}%`);
        console.log(`⏱️ Duração: ${duration}ms`);
        
        // Salvar relatório
        this.saveReport(report);
        
        return report;
    }
    
    generateRecommendations() {
        const recommendations = [];
        
        Object.entries(this.results).forEach(([testName, result]) => {
            if (!result.passed) {
                switch (testName) {
                    case 'manifest':
                        recommendations.push('Verificar manifest.json - campos obrigatórios faltando');
                        break;
                    case 'serviceWorker':
                        recommendations.push('Verificar Service Worker - funcionalidades essenciais faltando');
                        break;
                    case 'installation':
                        recommendations.push('Verificar meta tags de instalação PWA');
                        break;
                    case 'offline':
                        recommendations.push('Implementar funcionalidade offline completa');
                        break;
                    case 'update':
                        recommendations.push('Implementar sistema de atualização');
                        break;
                    case 'mobileNavigation':
                        recommendations.push('Verificar componentes de navegação mobile');
                        break;
                    case 'security':
                        recommendations.push('Implementar exclusões de cache para segurança');
                        break;
                    case 'accessibility':
                        recommendations.push('Melhorar acessibilidade - ARIA labels e landmarks');
                        break;
                    case 'performance':
                        recommendations.push('Implementar coleta de métricas de performance');
                        break;
                    case 'cache':
                        recommendations.push('Verificar estratégias de cache');
                        break;
                }
            }
        });
        
        return recommendations;
    }
    
    saveReport(report) {
        try {
            localStorage.setItem('pwa-test-report', JSON.stringify(report));
            console.log('💾 Relatório salvo no localStorage');
        } catch (error) {
            console.warn('⚠️ Não foi possível salvar relatório:', error);
        }
    }
}

// Executar testes automaticamente quando DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    if (window.location.pathname.includes('/admin/')) {
        // Aguardar um pouco para garantir que tudo carregou
        setTimeout(() => {
            const testSuite = new AutomatedPWATest();
            testSuite.runAllTests().then(() => {
                console.log('🎉 Testes automatizados concluídos!');
            });
        }, 2000);
    }
});

// Exportar para uso global
if (typeof window !== 'undefined') {
    window.AutomatedPWATest = AutomatedPWATest;
}
