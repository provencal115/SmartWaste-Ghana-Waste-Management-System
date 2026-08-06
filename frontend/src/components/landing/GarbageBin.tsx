import { motion } from 'framer-motion'
import { BIN_COLORS } from '@/lib/utils'

interface GarbageBinProps {
  size: 'small' | 'medium' | 'large'
  color: string
  className?: string
  delay?: number
  showLabel?: boolean
}

const sizeMap = {
  small: { w: 48, h: 64, lid: 52 },
  medium: { w: 64, h: 84, lid: 68 },
  large: { w: 80, h: 104, lid: 84 },
}

export function GarbageBin({ size, color, className, delay = 0, showLabel = false }: GarbageBinProps) {
  const dims = sizeMap[size]
  const fill = BIN_COLORS[color] || color

  return (
    <motion.div
      className={`relative cursor-pointer group ${className}`}
      animate={{ y: [0, -15, 0], rotate: [0, 2, -2, 0] }}
      transition={{ duration: 4 + delay, repeat: Infinity, ease: 'easeInOut', delay }}
      whileHover={{ scale: 1.15, transition: { duration: 0.2 } }}
    >
      <svg width={dims.w} height={dims.h + 12} viewBox={`0 0 ${dims.w} ${dims.h + 12}`}>
        <rect x={dims.w * 0.15} y={8} width={dims.lid} height={8} rx={2} fill={fill} opacity={0.9} />
        <rect x={dims.w * 0.2} y={4} width={dims.lid * 0.6} height={6} rx={3} fill={fill} />
        <rect x={dims.w * 0.1} y={16} width={dims.w * 0.8} height={dims.h - 16} rx={4} fill={fill} />
        <rect x={dims.w * 0.15} y={24} width={dims.w * 0.7} height={3} rx={1} fill="white" opacity={0.3} />
        <rect x={dims.w * 0.15} y={32} width={dims.w * 0.7} height={3} rx={1} fill="white" opacity={0.2} />
        <rect x={dims.w * 0.15} y={40} width={dims.w * 0.7} height={3} rx={1} fill="white" opacity={0.15} />
        <circle cx={dims.w * 0.5} cy={dims.h * 0.55} r={dims.w * 0.12} fill="white" opacity={0.15} />
      </svg>
      <motion.div
        className="absolute -bottom-8 left-1/2 -translate-x-1/2 opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap"
        initial={{ opacity: 0, y: 5 }}
        whileHover={{ opacity: 1, y: 0 }}
      >
        <span className="text-xs font-semibold bg-slate-900/80 text-white px-3 py-1 rounded-full capitalize">
          {size} Bin
        </span>
      </motion.div>
      {showLabel && (
        <p className="text-center text-xs font-medium mt-2 capitalize text-slate-600 dark:text-slate-400">{size}</p>
      )}
    </motion.div>
  )
}

export function FloatingBinsHero() {
  const bins = [
    { size: 'small' as const, color: 'green', x: '10%', y: '20%', delay: 0 },
    { size: 'medium' as const, color: 'blue', x: '25%', y: '50%', delay: 0.5 },
    { size: 'large' as const, color: 'black', x: '45%', y: '15%', delay: 1 },
    { size: 'small' as const, color: 'yellow', x: '60%', y: '45%', delay: 1.5 },
    { size: 'medium' as const, color: 'red', x: '75%', y: '25%', delay: 2 },
    { size: 'large' as const, color: 'green', x: '85%', y: '55%', delay: 0.8 },
    { size: 'small' as const, color: 'blue', x: '35%', y: '60%', delay: 1.2 },
    { size: 'medium' as const, color: 'yellow', x: '55%', y: '70%', delay: 0.3 },
    { size: 'large' as const, color: 'red', x: '15%', y: '70%', delay: 1.8 },
  ]

  return (
    <div className="absolute inset-0 overflow-hidden pointer-events-none">
      {bins.map((bin, i) => (
        <div key={i} className="absolute pointer-events-auto" style={{ left: bin.x, top: bin.y }}>
          <GarbageBin size={bin.size} color={bin.color} delay={bin.delay} />
        </div>
      ))}
    </div>
  )
}
