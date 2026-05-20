import { clsx } from "clsx";
import { twMerge } from "tailwind-merge";
import { cubicOut } from "svelte/easing";

export function cn(...inputs) {
    return twMerge(clsx(inputs));
}

export const flyAndScale = (
  node,
  params = { y: -8, x: 0, start: 0.95, duration: 150 }
) => {
  const style = getComputedStyle(node);
  const transform = style.transform === "none" ? "" : style.transform;

  return {
    duration: params.duration ?? 200,
    delay: 0,
    css: (t) => {
      const y = scaleConversion(t, [0, 1], [params.y ?? 5, 0]);
      const x = scaleConversion(t, [0, 1], [params.x ?? 0, 0]);
      const scale = scaleConversion(t, [0, 1], [params.start ?? 0.95, 1]);

      return `transform: ${transform} translate3d(${x}px, ${y}px, 0) scale(${scale}); opacity: ${t}`;
    },
    easing: cubicOut,
  };
};

function scaleConversion(valueA, scaleA, scaleB) {
  const [minA, maxA] = scaleA;
  const [minB, maxB] = scaleB;
  const percentage = (valueA - minA) / (maxA - minA);
  const valueB = percentage * (maxB - minB) + minB;
  return valueB;
}