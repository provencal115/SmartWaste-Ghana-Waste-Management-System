import { Link } from 'react-router-dom'
import { motion } from 'framer-motion'
import {
  Recycle, Truck, BarChart3, Shield, Smartphone, Zap, Leaf,
  ArrowRight, CheckCircle, MapPin, Clock, CreditCard
} from 'lucide-react'
import { Navbar } from '@/components/layout/Navbar'
import { FloatingBinsHero } from '@/components/landing/GarbageBin'
import { AnimatedCounter } from '@/components/common/AnimatedCounter'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'

const features = [
  { icon: Recycle, title: 'Smart Collection', desc: 'AI-optimized routes and intelligent scheduling for efficient waste pickup across Ghana.' },
  { icon: BarChart3, title: 'Real-time Analytics', desc: 'Interactive dashboards with collection performance, revenue trends, and customer growth metrics.' },
  { icon: Shield, title: 'Secure & Compliant', desc: 'Role-based access control, encrypted sessions, and comprehensive audit trails.' },
  { icon: Smartphone, title: 'Mobile-First Design', desc: 'Collectors work offline with automatic sync. Residents manage everything from their phone.' },
  { icon: Zap, title: 'Smart Predictions', desc: 'Bin fullness estimation, demand forecasting, and automated rescheduling after disruptions.' },
  { icon: Leaf, title: 'Eco-Friendly', desc: 'Track recycling metrics and promote sustainable waste management practices.' },
]

const plans = [
  { size: 'Small', capacity: '120L', weekly: 15, biweekly: 28, monthly: 50 },
  { size: 'Medium', capacity: '240L', weekly: 25, biweekly: 48, monthly: 90, popular: true },
  { size: 'Large', capacity: '360L', weekly: 40, biweekly: 75, monthly: 140 },
]

export default function LandingPage() {
  return (
    <div className="min-h-screen gradient-bg">
      <Navbar />

      {/* Hero */}
      <section className="relative min-h-screen flex items-center pt-16 overflow-hidden">
        <FloatingBinsHero />
        <div className="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 grid lg:grid-cols-2 gap-12 items-center">
          <motion.div initial={{ opacity: 0, x: -30 }} animate={{ opacity: 1, x: 0 }} transition={{ duration: 0.7 }}>
            <div className="inline-flex items-center gap-2 px-4 py-2 rounded-full glass text-sm font-medium mb-6">
              <Leaf className="w-4 h-4 text-emerald-600" />
              Smart Waste Management for Ghana
            </div>
            <h1 className="text-5xl md:text-6xl lg:text-7xl font-bold leading-tight">
              Cleaner Cities,<br />
              <span className="text-gradient">Smarter Waste</span>
            </h1>
            <p className="mt-6 text-lg text-slate-600 dark:text-slate-400 max-w-lg">
              A complete waste management platform for municipalities and private companies.
              Schedule pickups, track bins, manage inventory, and optimize collection routes — all in one place.
            </p>
            <div className="mt-8 flex flex-wrap gap-4">
              <Link to="/register">
                <Button size="lg" className="group">
                  Start Free Trial <ArrowRight className="w-4 h-4 group-hover:translate-x-1 transition-transform" />
                </Button>
              </Link>
              <Link to="/login"><Button variant="outline" size="lg">Sign In</Button></Link>
            </div>
            <div className="mt-8 flex items-center gap-6 text-sm text-slate-500">
              <span className="flex items-center gap-1"><CheckCircle className="w-4 h-4 text-emerald-500" /> No setup fees</span>
              <span className="flex items-center gap-1"><CheckCircle className="w-4 h-4 text-emerald-500" /> 24/7 Support</span>
            </div>
          </motion.div>
          <motion.div initial={{ opacity: 0, scale: 0.9 }} animate={{ opacity: 1, scale: 1 }} transition={{ duration: 0.7, delay: 0.2 }} className="hidden lg:block">
            <div className="glass-card p-8 space-y-6">
              <div className="flex items-center gap-4">
                <div className="p-3 bg-emerald-100 dark:bg-emerald-900/30 rounded-xl"><Truck className="w-8 h-8 text-emerald-600" /></div>
                <div><p className="font-semibold">Live Collection Tracking</p><p className="text-sm text-slate-500">847 pickups completed today</p></div>
              </div>
              <div className="flex items-center gap-4">
                <div className="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-xl"><MapPin className="w-8 h-8 text-blue-600" /></div>
                <div><p className="font-semibold">Route Optimization</p><p className="text-sm text-slate-500">32% faster collection routes</p></div>
              </div>
              <div className="flex items-center gap-4">
                <div className="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-xl"><Clock className="w-8 h-8 text-purple-600" /></div>
                <div><p className="font-semibold">Smart Scheduling</p><p className="text-sm text-slate-500">Automated pickup reminders</p></div>
              </div>
            </div>
          </motion.div>
        </div>
      </section>

      {/* Stats */}
      <section id="stats" className="py-20">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="grid grid-cols-2 md:grid-cols-4 gap-8">
            <AnimatedCounter end={12500} suffix="+" label="Active Residents" />
            <AnimatedCounter end={98500} suffix="+" label="Collections Completed" />
            <AnimatedCounter end={8200} suffix="+" label="Dustbins Assigned" />
            <AnimatedCounter end={45} suffix="+" label="Collection Trucks" />
          </div>
        </div>
      </section>

      {/* Features */}
      <section id="features" className="py-20">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <motion.div initial={{ opacity: 0, y: 20 }} whileInView={{ opacity: 1, y: 0 }} viewport={{ once: true }} className="text-center mb-16">
            <h2 className="text-4xl font-bold">Everything You Need</h2>
            <p className="mt-4 text-slate-600 dark:text-slate-400 max-w-2xl mx-auto">
              From resident registration to fleet management, our platform covers every aspect of modern waste management.
            </p>
          </motion.div>
          <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            {features.map((f, i) => (
              <motion.div key={i} initial={{ opacity: 0, y: 20 }} whileInView={{ opacity: 1, y: 0 }} viewport={{ once: true }} transition={{ delay: i * 0.1 }}>
                <Card className="h-full hover:border-emerald-500/30">
                  <CardContent className="pt-2">
                    <div className="p-3 bg-emerald-100 dark:bg-emerald-900/30 rounded-xl w-fit mb-4">
                      <f.icon className="w-6 h-6 text-emerald-600" />
                    </div>
                    <h3 className="text-lg font-semibold mb-2">{f.title}</h3>
                    <p className="text-sm text-slate-600 dark:text-slate-400">{f.desc}</p>
                  </CardContent>
                </Card>
              </motion.div>
            ))}
          </div>
        </div>
      </section>

      {/* Pricing */}
      <section id="pricing" className="py-20">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <motion.div initial={{ opacity: 0, y: 20 }} whileInView={{ opacity: 1, y: 0 }} viewport={{ once: true }} className="text-center mb-16">
            <h2 className="text-4xl font-bold">Simple, Transparent Pricing</h2>
            <p className="mt-4 text-slate-600 dark:text-slate-400">Choose your bin size and payment plan. No hidden fees.</p>
          </motion.div>
          <div className="grid md:grid-cols-3 gap-8 max-w-4xl mx-auto">
            {plans.map((plan, i) => (
              <motion.div key={i} initial={{ opacity: 0, y: 20 }} whileInView={{ opacity: 1, y: 0 }} viewport={{ once: true }} transition={{ delay: i * 0.15 }}>
                <Card className={`h-full text-center ${plan.popular ? 'ring-2 ring-emerald-500 relative' : ''}`}>
                  {plan.popular && <span className="absolute -top-3 left-1/2 -translate-x-1/2 bg-emerald-600 text-white text-xs font-bold px-3 py-1 rounded-full">Most Popular</span>}
                  <CardContent className="pt-6 space-y-4">
                    <h3 className="text-xl font-bold">{plan.size} Bin</h3>
                    <p className="text-sm text-slate-500">{plan.capacity} capacity</p>
                    <div className="space-y-2 text-sm">
                      <p><CreditCard className="w-4 h-4 inline mr-1" />Weekly: <strong>GHS {plan.weekly}</strong></p>
                      <p>Bi-weekly: <strong>GHS {plan.biweekly}</strong></p>
                      <p>Monthly: <strong>GHS {plan.monthly}</strong></p>
                    </div>
                    <Link to="/register"><Button className="w-full" variant={plan.popular ? 'default' : 'outline'}>Get Started</Button></Link>
                  </CardContent>
                </Card>
              </motion.div>
            ))}
          </div>
        </div>
      </section>

      {/* CTA */}
      <section className="py-20">
        <div className="max-w-4xl mx-auto px-4 text-center">
          <motion.div initial={{ opacity: 0, scale: 0.95 }} whileInView={{ opacity: 1, scale: 1 }} viewport={{ once: true }} className="glass-card p-12">
            <h2 className="text-3xl md:text-4xl font-bold">Ready to Transform Waste Management?</h2>
            <p className="mt-4 text-slate-600 dark:text-slate-400">Join thousands of residents and municipalities already using SmartWaste.</p>
            <Link to="/register"><Button size="lg" className="mt-8">Create Your Account</Button></Link>
          </motion.div>
        </div>
      </section>

      <footer className="border-t border-slate-200 dark:border-slate-800 py-8">
        <div className="max-w-7xl mx-auto px-4 flex flex-col md:flex-row items-center justify-between gap-4">
          <div className="flex items-center gap-2">
            <Recycle className="w-5 h-5 text-emerald-600" />
            <span className="font-bold">SmartWaste Ghana</span>
          </div>
          <p className="text-sm text-slate-500">&copy; 2026 SmartWaste. All rights reserved.</p>
        </div>
      </footer>
    </div>
  )
}
